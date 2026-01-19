<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
use App\Http\Resources\BaseResource;

class GradeOneApiController extends Controller
{
    private function getConnection(Request $request): string
    {
        return $request->query('database', session('database', 'jo'));
    }

    /**
     * GET /api/grades
     * جلب الصفوف الدراسية
     */
    public function index(Request $request)
    {
        $db = $this->getConnection($request);

        $classes = SchoolClass::on($db)->get();

        return new BaseResource([
            'classes' => $classes
        ]);
    }

    /**
     * GET /api/grades/{id}
     * جلب بيانات صف واحد
     */
    public function show(Request $request, $id)
    {
        $db = $this->getConnection($request);

        $class = SchoolClass::on($db)->find($id);

        if (!$class) {
            return (new BaseResource(['message' => 'Class not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        return new BaseResource([
            'class' => $class
        ]);
    }

    /**
     * GET /api/subjects/{id}
     * جلب مادة + فصول دراسية
     */
    public function showSubject(Request $request, $id)
    {
        $db = $this->getConnection($request);

        $subject = Subject::on($db)->find($id);

        if (!$subject) {
            return (new BaseResource(['message' => 'Subject not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        $semesters = Semester::on($db)
            ->where('grade_level', $subject->grade_level)
            ->orderBy('semester_name')
            ->get();

        return new BaseResource([
            'subject' => $subject,
            'semesters' => $semesters
        ]);
    }

    /**
     * GET /api/subjects/{subject}/semesters/{semester}/category/{category}
     */
    public function subjectArticles(Request $request, $subject, $semester, $category)
    {
        $db = $this->getConnection($request);

        $subjectModel = Subject::on($db)->find($subject);
        $semesterModel = Semester::on($db)->find($semester);

        if (!$subjectModel || !$semesterModel) {
            return (new BaseResource(['message' => 'Subject or semester not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        $articles = Article::on($db)
            ->where('subject_id', $subject)
            ->where('semester_id', $semester)
            ->whereHas('files', fn($q) => $q->where('file_category', $category))
            ->with(['files' => fn($q) => $q->where('file_category', $category)])
            ->latest()
            ->paginate(10);

        return new BaseResource([
            'articles' => $articles,
            'subject' => $subjectModel,
            'semester' => $semesterModel,
            'category' => $category
        ]);
    }

    /**
     * GET /api/articles/{id}
     * جلب مقالة كاملة + كلمات مفتاحية + الملفات + المؤلف
     */
    public function showArticle(Request $request, $id)
    {
        $db = $this->getConnection($request);

        $cacheKey = "article_full_{$db}_{$id}";

        $article = Cache::remember($cacheKey, 1800, function () use ($db, $id) {
            return Article::on($db)
                ->with([
                    'subject.schoolClass',
                    'semester',
                    'schoolClass',
                    'keywords',
                    'files'
                ])
                ->with(['comments' => function ($q) {
                    $q->latest()
                      ->limit(10)
                      ->with(['user:id,name,avatar,profile_photo_path']);
                }])
                ->find($id);
        });

        if (!$article) {
            return (new BaseResource(['message' => 'Article not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        $article->increment('visit_count');

        // Cache author data separately
        $authorCacheKey = "author_{$article->author_id}";
        $author = Cache::remember($authorCacheKey, 600, function () use ($article) {
            return User::on('jo')->select('id','name')->find($article->author_id);
        });

        return new BaseResource([
            'article' => $article,
            'author' => $author
        ]);
    }

    /**
     * GET /api/files/{id}/download
     */
    public function downloadFile(Request $request, $id)
    {
        $db = $this->getConnection($request);

        $file = File::on($db)->find($id);

        if (!$file) {
            return (new BaseResource(['message' => 'File not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        $file->increment('download_count');

        $filePath = storage_path('app/public/' . $file->file_path);

        if (!file_exists($filePath)) {
            return (new BaseResource(['message' => 'File does not exist on the server']))
                ->response($request)
                ->setStatusCode(404);
        }

        return response()->download($filePath, $file->file_Name);
    }
}
