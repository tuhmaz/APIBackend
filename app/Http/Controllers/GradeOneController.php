<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Article;
use App\Models\File;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GradeOneController extends Controller
{
  public function setDatabase(Request $request)
  {
    $request->validate([
      'database' => 'required|string|in:jo,sa,eg,ps'
    ]);


    $request->session()->put('database', $request->input('database'));

    return redirect()->back();
  }

  private function getConnection(Request $request)
  {

    return $request->session()->get('database', 'jo');
  }

  public function index(Request $request)
  {
    $database = $this->getConnection($request);


    $lesson = SchoolClass::on($database)->get();
    $classes = SchoolClass::on($database)->get();

    return view('content.frontend.lesson.index', compact('lesson', 'classes'));
  }

  public function show(Request $request, $database, $id)
  {
    $database = $this->getConnection($request);
    
    // Use cache for lesson data
    $cacheKey = "lesson_{$database}_{$id}";
    $lesson = Cache::remember($cacheKey, 600, function () use ($database, $id) {
      return SchoolClass::on($database)->findOrFail($id);
    });
    
    $class = $lesson; // Avoid duplicate query

    return view('content.frontend.lesson.show', compact('lesson','class', 'database'));
  }



  public function showSubject(Request $request, $database, $id)
  {
    $database = $this->getConnection($request);
    $database = $request->session()->get('database', 'jo');

    // Use cache for subject data
    $cacheKey = "subject_{$database}_{$id}";
    $subject = Cache::remember($cacheKey, 600, function () use ($database, $id) {
      return Subject::on($database)->findOrFail($id);
    });
    
    $gradeLevel = $subject->grade_level;
    
    // Use cache for semesters
    $semesterCacheKey = "semesters_{$database}_{$gradeLevel}";
    $semesters = Cache::remember($semesterCacheKey, 300, function () use ($database, $gradeLevel) {
      return Semester::on($database)
        ->where('grade_level', $gradeLevel)
        ->orderBy('semester_name')
        ->get();
    });

    return view('content.frontend.subject.show', compact('subject', 'semesters', 'database'));
  }

  public function subjectArticles(Request $request, $database, Subject $subject, Semester $semester, $category)
{
  $database = $this->getConnection($request);

  // Use cache for articles with files
  $cacheKey = "subject_articles_{$database}_{$subject->id}_{$semester->id}_{$category}";
  $articles = Cache::remember($cacheKey, 180, function () use ($database, $subject, $semester, $category) {
    return Article::on($database)
      ->where('subject_id', $subject->id)
      ->where('semester_id', $semester->id)
      ->whereHas('files', function ($query) use ($category) {
          $query->where('file_category', $category);
      })
      ->with(['files' => function ($query) use ($category) {
          $query->where('file_category', $category);
      }])
      ->orderBy('created_at', 'desc')
      ->paginate(10);
  });

  // Use cache for grade level
  $gradeCacheKey = "grade_level_{$database}_{$subject->id}";
  $grade_level = Cache::remember($gradeCacheKey, 600, function () use ($database, $subject) {
    $subject->setConnection($database);
    return $subject->schoolClass->grade_name;
  });

  return view('content.frontend.articles.index', compact('articles', 'subject', 'semester', 'category', 'grade_level', 'database'));
}


  public function showArticle(Request $request, $database, $id)
  {
    // Resolve active database (prefer route param, fallback to session)
    $database = $database ?: session('database', 'jo');

    // Performance instrumentation (debug/local only)
    $t0 = microtime(true);
    $enablePerf = app()->environment('local') || (bool) config('app.debug');
    if ($enablePerf) {
      try {
        DB::listen(function ($query) {
          $timeMs = $query->time ?? 0;
          if ($timeMs > 150) {
            Log::warning('[Perf] Slow query', [
              'sql' => $query->sql,
              'bindings' => $query->bindings,
              'time_ms' => $timeMs,
              'connection' => $query->connectionName ?? null,
            ]);
          }
        });
      } catch (\Throwable $e) {
        // ignore if listen is already attached
      }

      // Enable query log for involved connections
      try { DB::connection($database)->enableQueryLog(); } catch (\Throwable $e) {}
      try { DB::connection('jo')->enableQueryLog(); } catch (\Throwable $e) {}
    }

    // Use caching to reduce database load for frequently accessed articles
    $articleCacheKey = sprintf('article_full_%s_%d', $database, $id);

    $article = Cache::remember($articleCacheKey, now()->addMinutes(30), function () use ($database, $id) {
      // Eager-load all relations used by the Blade to prevent N+1 queries
      return Article::on($database)
        ->with([
          'subject.schoolClass',
          'semester',
          'schoolClass',
          'keywords',
          'files',
        ])
        // Load only latest 10 comments with optimized eager loading
        ->with(['comments' => function ($q) use ($database) {
          $q->latest()
            ->limit(10)
            ->select(['id', 'commentable_id', 'commentable_type', 'user_id', 'body', 'database', 'created_at', 'updated_at'])
            ->with(['user' => function ($userQuery) {
              $userQuery->select(['id', 'name', 'avatar', 'profile_photo_path']);
            }]);
        }])
        ->findOrFail($id);
    });

    // Use the eager-loaded files relation
    $file = $article->files->first();
    $category = $file ? $file->file_category : 'articles';

    $subject = $article->subject;
    $semester = $article->semester;
    $grade_level = $subject->schoolClass->grade_name;

    // Precompute keywords list for lighter Blade usage
    $keywordsList = $article->keywords->pluck('keyword')->filter()->values()->all();

    // Increment visits (single lightweight query)
    $article->increment('visit_count');

    // Author is stored on the primary connection ('jo')
    $authorQuery = User::on('jo');
    try {
      $usersTable = (new User())->getTable();
      if (Schema::connection('jo')->hasColumn($usersTable, 'twitter_handle')) {
        $authorQuery->select(['id','name','twitter_handle']);
      } else {
        $authorQuery->select(['id','name']);
      }
    } catch (\Throwable $e) {
      $authorQuery->select(['id','name']);
    }
    $author = $authorQuery->find($article->author_id);

    // Keyword link replacements (single pass + cached)
    $cacheKey = sprintf('article_rendered_%s_%d_%d', $database, $article->id, $article->updated_at?->getTimestamp() ?? 0);
    $processedContent = Cache::remember($cacheKey, now()->addHours(6), function () use ($article, $database) {
      return $this->replaceKeywordsWithLinks($article->content, $article->keywords, $database);
    });
    $article->content = $processedContent;

    $response = view('content.frontend.articles.show', compact('article', 'subject', 'semester', 'grade_level', 'category', 'database', 'author', 'keywordsList'));

    if ($enablePerf) {
      // Log performance summary
      $durationMs = (microtime(true) - $t0) * 1000;
      try {
        $queriesDb = DB::connection($database)->getQueryLog();
      } catch (\Throwable $e) { $queriesDb = []; }
      try {
        $queriesJo = DB::connection('jo')->getQueryLog();
      } catch (\Throwable $e) { $queriesJo = []; }

      Log::info('[Perf] showArticle summary', [
        'db' => $database,
        'article_id' => $id,
        'duration_ms' => round($durationMs, 2),
        'queries_'.$database => count($queriesDb),
        'queries_jo' => count($queriesJo),
      ]);
    }

    return $response;
  }


  private function createInternalLinks($content, $keywords, $database)
  {

    $keywordsArray = $keywords->pluck('keyword')->toArray();

    foreach ($keywordsArray as $keyword) {
      $keyword = trim($keyword);
      // Skip empty or null keywords to avoid route generation errors
      if ($keyword === '' || $keyword === null) {
        continue;
      }
      $url = route('keywords.indexByKeyword', ['database' => $database, 'keywords' => $keyword]);
      $content = str_replace($keyword, '<a href="' . $url . '">' . $keyword . '</a>', $content);
    }

    return $content;
  }

  private function replaceKeywordsWithLinks($content, $keywords, $database)
  {
    foreach ($keywords as $keyword) {
      $keywordText = trim((string) $keyword->keyword);

      // Skip empty/null keywords to prevent missing parameter errors
      if ($keywordText === '') {
        continue;
      }

      // تمرير معلمة database بالإضافة إلى keyword
      $keywordLink = route('keywords.indexByKeyword', ['database' => $database, 'keywords' => $keywordText]);

      // استبدال الكلمة الدلالية بالرابط الخاص بها
      $content = preg_replace('/\b' . preg_quote($keywordText, '/') . '\b/', '<a href="' . $keywordLink . '">' . $keywordText . '</a>', $content);
    }


    return $content;
  }



  public function downloadFile(Request $request, $id)
  {
    $database = $this->getConnection($request);


    $file = File::on($database)->findOrFail($id);


    $file->increment('download_count');


    $filePath = storage_path('app/public/' . $file->file_path);
    if (file_exists($filePath)) {
      return response()->download($filePath);
    }

    return redirect()->back()->with('error', 'File not found.');
  }
}
