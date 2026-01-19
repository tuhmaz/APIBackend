<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Subject;
use App\Models\Semester;
use App\Models\SchoolClass;
use App\Models\File;
use App\Models\User;
use App\Models\Keyword;
use App\Models\Comment;
use Illuminate\Support\Facades\Cache;
use App\Notifications\ArticleNotification;
use App\Services\SecureFileUploadService;
use App\Services\OneSignalService;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\ArticleCollection;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ArticleApiController extends Controller
{
    /**
     * @var SecureFileUploadService
     */
    protected $secureFileUploadService;

    /**
     * @var OneSignalService
     */
    protected $oneSignalService;

    public function __construct(
        SecureFileUploadService $secureFileUploadService,
        OneSignalService $oneSignalService
    ) {
        $this->secureFileUploadService = $secureFileUploadService;
        $this->oneSignalService = $oneSignalService;

        // API عادة ستستخدم auth:sanctum في routes/api.php
        // هنا نضمن أن المستخدم مصدّق
        $this->middleware('auth:sanctum')->except([
            'index',
            'show',
            'indexByClass',
            'indexByKeyword',
            'download'
        ]);
    }

    /**
     * خريطة الدول المتاحة
     */
    private function getAvailableCountries(): array
    {
        return [
            ['id' => 1, 'name' => 'الأردن',   'code' => 'jo'],
            ['id' => 2, 'name' => 'السعودية', 'code' => 'sa'],
            ['id' => 3, 'name' => 'مصر',      'code' => 'eg'],
            ['id' => 4, 'name' => 'فلسطين',   'code' => 'ps'],
        ];
    }

    /**
     * تحديد اتصال قاعدة البيانات حسب الدولة
     */
    private function getConnection($countryId = null): string
    {
        if (!$countryId) {
            return 'jo';
        }

        if (is_string($countryId)) {
            $code = strtolower($countryId);
            if (ctype_digit($code)) {
                $countryId = (int) $code;
            } else {
                return in_array($code, ['jo','sa','eg','ps']) ? $code : 'jo';
            }
        }

        $countryId = (int) $countryId;

        return match ($countryId) {
            2 => 'sa',
            3 => 'eg',
            4 => 'ps',
            default => 'jo',
        };
    }

    private function forgetArticleCache(string $database, int $articleId, ?int $previousSubjectId = null, ?int $currentSubjectId = null): void
    {
        Cache::forget("article_{$database}_{$articleId}");

        if ($previousSubjectId) {
            Cache::forget("related_articles_{$database}_{$previousSubjectId}_{$articleId}");
        }

        if ($currentSubjectId && $currentSubjectId !== $previousSubjectId) {
            Cache::forget("related_articles_{$database}_{$currentSubjectId}_{$articleId}");
        }

        // Clear filter cache for this database
        $this->clearFilterCache($database);
    }

    /**
     * Clear all filter-related cache for a database
     */
    private function clearFilterCache(string $database): void
    {
        // Clear school classes cache
        Cache::forget("school_classes_{$database}");

        // Use Cache tags if available (Redis/Memcached)
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags(["filter_{$database}"])->flush();
        }

        // For file-based cache, we need to clear filter_articles and filter_files keys
        // Since we can't use wildcards, we'll use a marker to invalidate
        // Set a cache version that filter queries will check
        $cacheVersion = time();
        Cache::put("filter_cache_version_{$database}", $cacheVersion, 3600);
    }

    /**
     * GET /api/dashboard/articles/stats
     * إحصائيات المقالات
     */
    public function stats(Request $request)
    {
        try {
            $countryParam = $request->input('database', $request->input('country_id', $request->input('country', '1')));
            $connection   = $this->getConnection($countryParam);

            $total     = Article::on($connection)->count();
            $views     = Article::on($connection)->sum('visit_count');
            $published = Article::on($connection)->where('status', true)->count();
            $drafts    = Article::on($connection)->where('status', false)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total'     => $total,
                    'views'     => $views,
                    'published' => $published,
                    'drafts'    => $drafts,
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Error in ArticleApiController@stats: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load stats'], 500);
        }
    }

    /**
     * GET /api/articles
     * قائمة المقالات مع paginate
     */
    public function index(Request $request)
    {
        $countryParam = $request->input('database', $request->input('country_id', $request->input('country', 1)));
        $connection   = $this->getConnection($countryParam);

        $perPage = (int) $request->input('per_page', 15);

        $query = Article::on($connection)
            ->with(['schoolClass', 'subject', 'semester', 'keywords', 'files'])
            ->orderByDesc('created_at');

        // يمكن إضافة فلاتر اختيارية مثلاً بالعنوان أو المادة لاحقاً
        if ($search = $request->input('q')) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        if ($subjectId = $request->input('subject_id')) {
            $query->where('subject_id', $subjectId);
        }

        if ($semesterId = $request->input('semester_id')) {
            $query->where('semester_id', $semesterId);
        }

        if ($classId = $request->input('class_id')) {
             // Assuming grade_level is the foreign key for SchoolClass in Article model, 
             // OR there is a school_class_id column. 
             // Looking at Article model, it has 'grade_level' in fillable.
             // But ArticleResource likely maps it.
             // Let's check Article model relationships again.
             // Article model says: public function schoolClass() { ... }
             // I'll assume the column is 'grade_level' based on fillable, 
             // but usually it's school_class_id.
             // Let's check Article model relationship definition.
             $query->whereHas('schoolClass', function($q) use ($classId) {
                 $q->where('id', $classId);
             });
        }

        if ($category = $request->input('file_category')) {
            $query->whereHas('files', function ($q) use ($category) {
                $q->where('file_category', $category);
            });
        }

        if (!is_null($request->input('status'))) {
            $status = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN);
            $query->where('status', $status);
        }

        $articles = $query->paginate($perPage);

        return (new ArticleCollection($articles->items()))
            ->additional([
                'pagination' => [
                    'current_page' => $articles->currentPage(),
                    'per_page'     => $articles->perPage(),
                    'total'        => $articles->total(),
                    'last_page'    => $articles->lastPage(),
                ],
                'country' => $countryParam,
            ]);
    }

    /**
     * GET /api/articles/create
     * بيانات المساعدة لإنشاء مقال (قوائم الصفوف، المواد، الفصول)
     */
    public function create(Request $request)
    {
        $countryParam = $request->input('database', $request->input('country_id', $request->input('country', 1)));
        $connection   = $this->getConnection($countryParam);

        $classes   = SchoolClass::on($connection)->get();
        $subjects  = Subject::on($connection)->get();
        $semesters = Semester::on($connection)->get();

        return new BaseResource([
            'country'   => $countryParam,
            'classes'   => $classes,
            'subjects'  => $subjects,
            'semesters' => $semesters,
        ]);
    }

    /**
     * POST /api/articles
     * إنشاء مقال جديد
     */
    public function store(Request $request)
    {
        $countryParam = $request->input('database', $request->input('country_id', $request->input('country', '1')));
        $connection   = $this->getConnection($countryParam);

        $validated = $request->validate([
            'country'      => 'required|string',
            'class_id'     => 'required|exists:school_classes,id',
            'subject_id'   => 'required|exists:subjects,id',
            'semester_id'  => 'required|exists:semesters,id',
            'title'        => [
                'required',
                'string',
                'max:60',
                function ($attribute, $value, $fail) use ($connection) {
                    $existingArticle = Article::on($connection)->where('title', $value)->first();
                    if ($existingArticle) {
                        $fail('عنوان المقال موجود بالفعل. يرجى اختيار عنوان فريد.');
                    }
                }
            ],
            'content'          => 'required',
            'keywords'         => 'nullable|string',
            'file_category'    => 'nullable|string',
            'file'             => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar,jpg,jpeg,png,gif,webp|max:51200',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240|dimensions:min_width=100,min_height=100',
            'meta_description' => 'nullable|string|max:120',
            'file_name'        => 'nullable|string|max:255',
            'status'           => 'nullable|boolean',
        ]);

        $article = null;

        DB::connection($connection)->transaction(function () use ($request, $validated, $countryParam, $connection, &$article) {

            $metaDescription = $request->meta_description;

            if ($request->boolean('use_title_for_meta') && !$metaDescription) {
                $metaDescription = Str::limit($request->title, 120);
            }

            if ($request->boolean('use_keywords_for_meta') && !$metaDescription && $request->keywords) {
                $metaDescription = Str::limit($request->keywords, 120);
            }

            if (!$metaDescription) {
                $metaDescription = Str::limit(strip_tags($request->content), 120);
            }

            // صورة المقال
            $imagePath = 'default.webp';
            if ($request->hasFile('image')) {
                $imagePath = $this->securelyStoreArticleImage($request->file('image'));
            }

            $article = Article::on($connection)->create([
                'grade_level'      => $request->class_id,
                'subject_id'       => $request->subject_id,
                'semester_id'      => $request->semester_id,
                'title'            => $request->title,
                'content'          => $request->content,
                'meta_description' => $metaDescription,
                'author_id'        => Auth::id(),
                'status'           => $request->has('status') ? (bool) $request->status : false,
                'image'            => $imagePath,
            ]);

            // الكلمات المفتاحية
            if ($request->keywords) {
                $keywords = array_map('trim', explode(',', $request->keywords));

                foreach ($keywords as $keyword) {
                    if ($keyword === '') {
                        continue;
                    }
                    $keywordModel = Keyword::on($connection)->firstOrCreate(['keyword' => $keyword]);
                    $article->keywords()->attach($keywordModel->id);
                }
            }

            // الملف المرفق
            if ($request->hasFile('file')) {
                try {
                    $schoolClass   = SchoolClass::on($connection)->find($request->class_id);
                    $folderName    = $schoolClass ? $schoolClass->grade_name : 'General';
                    $folderCategory = Str::slug($request->file_category);

                    $countrySlug   = Str::slug($countryParam);
                    $folderNameSlug = Str::slug($folderName);

                    $folderPath = "files/$countrySlug/$folderNameSlug/$folderCategory";

                    $customFilename = $request->input('file_name');

                    $fileInfo = $this->securelyStoreAttachment(
                        $request->file('file'),
                        $folderPath,
                        $customFilename
                    );

                    File::on($connection)->create([
                        'article_id'    => $article->id,
                        'file_path'     => $fileInfo['path'],
                        'file_type'     => $fileInfo['extension'],
                        'file_category' => $request->file_category,
                        'file_name'     => $fileInfo['filename'],
                        'file_size'     => $fileInfo['size'],
                        'mime_type'     => $fileInfo['mime_type']
                    ]);
                } catch (\Exception $e) {
                    Log::error('فشل في تخزين الملف المرفق: ' . $e->getMessage());
                }
            }

            // Clear cache for related data
            $this->clearFilterCache($connection);

            // إشعار OneSignal + Notifications
            $this->sendNotification($article);

            // إرسال التنبيهات لكل الأعضاء (كما في الأصل) - use queue for better performance
            if (class_exists('\App\Jobs\SendArticleNotificationsJob')) {
                \App\Jobs\SendArticleNotificationsJob::dispatch($article);
            } else {
                User::select('id', 'name', 'email')
                    ->chunk(200, function ($users) use ($article) {
                        foreach ($users as $user) {
                            $user->notify(new ArticleNotification($article));
                        }
                    });
            }
        });

        if (!$article) {
            return (new BaseResource(['message' => 'Failed to create article']))
                ->response($request)
                ->setStatusCode(500);
        }

        return (new ArticleResource($article->load(['schoolClass', 'subject', 'semester', 'keywords', 'files'])))
            ->response($request)
            ->setStatusCode(201);
    }

    /**
     * GET /api/articles/{id}
     * عرض مقال واحد + توليد روابط داخلية + زيادة visit_count
     */
    public function show(Request $request, $id)
    {
        try {
            $countryParam = $request->input('database', $request->input('country_id', $request->input('country', '1')));
            $database     = $this->getConnection($countryParam);

            // Use cache for article data
            $cacheKey = "article_{$database}_{$id}";
            
            $article = Cache::remember($cacheKey, 300, function () use ($database, $id) {
                return Article::on($database)
                    ->with(['files', 'subject', 'semester', 'schoolClass', 'keywords'])
                    ->find($id);
            });

            if (!$article) {
                return response()->json(['message' => 'المقال غير موجود'], 404);
            }

            // Increment visit count in background (non-blocking)
            Article::on($database)->where('id', $id)->increment('visit_count');

            // Get author from cache
            $author = null;
            if ($article->author_id) {
                $authorCacheKey = "author_{$article->author_id}";
                $author = Cache::remember($authorCacheKey, 600, function () use ($article) {
                    return User::on('jo')->select(['id', 'name'])->find($article->author_id);
                });
            }

            // Skip keyword replacement for performance - send raw content
            $contentWithKeywords = $article->content;

            // Get related articles from cache
            $relatedCacheKey = "related_articles_{$database}_{$article->subject_id}_{$id}";
            $relatedArticles = Cache::remember($relatedCacheKey, 180, function () use ($database, $article, $id) {
                return Article::on($database)
                    ->select(['id', 'title', 'created_at', 'visit_count'])
                    ->where('subject_id', $article->subject_id)
                    ->where('id', '!=', $id)
                    ->where('status', true)
                    ->orderByDesc('id')
                    ->limit(6)
                    ->get();
            });

            // Return response
            return (new ArticleResource($article))->additional([
                'country' => $database,
                'author_details' => $author,
                'content_with_keywords' => $contentWithKeywords,
                'comments' => [],
                'related_articles' => $relatedArticles
            ]);
        } catch (\Throwable $e) {
            Log::error('Error in ArticleApiController@show: ' . $e->getMessage());
            return response()->json(['error' => 'حدث خطأ في تحميل المقال'], 500);
        }
    }

    /**
     * استبدال الكلمات المفتاحية بروابط
     */
    private function replaceKeywordsWithLinks($content, $keywords, $database = 'jo')
    {
        foreach ($keywords as $keyword) {
            $keywordText = trim((string) $keyword->keyword);

            if ($keywordText === '') {
                continue;
            }

            // Construct frontend URL: /{countryCode}/articles/keyword/{keyword}
            // Note: We are assuming the frontend structure here.
            $frontendUrl = "/{$database}/articles/keyword/" . urlencode($keywordText);

            $content = preg_replace(
                '/\b' . preg_quote($keywordText, '/') . '\b/u',
                '<a href="' . $frontendUrl . '" class="text-primary hover:underline font-medium">' . $keywordText . '</a>',
                $content
            );
        }

        return $content;
    }

    /**
     * GET /api/articles/{id}/edit
     * بيانات تعديل المقال (المقال + القوائم)
     */
    public function edit(Request $request, $id)
    {
        $country    = $request->input('country', '1');
        $connection = $this->getConnection($country);

        $article = Article::on($connection)->with('files', 'keywords')->findOrFail($id);

        $classes   = SchoolClass::on($connection)->get();
        $subjects  = Subject::on($connection)->where('grade_level', $article->grade_level)->get();
        $semesters = Semester::on($connection)->where('grade_level', $article->grade_level)->get();

        return (new ArticleResource($article))
            ->additional([
                'country' => $country,
                'classes' => $classes,
                'subjects' => $subjects,
                'semesters' => $semesters,
            ]);
    }

    /**
     * PUT /api/articles/{id}
     * تحديث مقال
     */
    public function update(Request $request, $id)
    {
        $countryParam = $request->input('database', $request->input('country_id', $request->input('country', '1')));
        $connection   = $this->getConnection($countryParam);

        $validated = $request->validate([
            'class_id'      => 'required|exists:school_classes,id',
            'subject_id'    => 'required|exists:subjects,id',
            'semester_id'   => 'required|exists:semesters,id',
            'title'         => [
                'required',
                'string',
                'max:60',
                function ($attribute, $value, $fail) use ($connection, $id) {
                    $existingArticle = Article::on($connection)
                        ->where('title', $value)
                        ->where('id', '!=', $id)
                        ->first();
                    if ($existingArticle) {
                        $fail('عنوان المقال موجود بالفعل. يرجى اختيار عنوان فريد.');
                    }
                }
            ],
            'content'          => 'required',
            'keywords'         => 'nullable|string',
            'file_category'    => 'nullable|string',
            'file'             => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar,jpg,jpeg,png,gif,webp|max:51200',
            'new_file'         => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar,jpg,jpeg,png,gif,webp|max:51200',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240|dimensions:min_width=100,min_height=100',
            'meta_description' => 'nullable|string|max:120',
            'file_name'        => 'nullable|string|max:255',
            'status'           => 'nullable|boolean',
        ]);

        $article = Article::on($connection)->with('files', 'keywords')->findOrFail($id);
        $previousSubjectId = $article->subject_id ? (int) $article->subject_id : null;

        $metaDescription = $request->meta_description;
        if (!$metaDescription) {
            if ($request->keywords) {
                $metaDescription = Str::limit($request->keywords, 120);
            } else {
                $metaDescription = Str::limit(strip_tags($request->content), 120);
            }
        }

        $article->grade_level      = $request->class_id;
        $article->subject_id       = $request->subject_id;
        $article->semester_id      = $request->semester_id;
        $article->title            = $request->title;
        $article->content          = $request->content;
        $article->meta_description = $metaDescription;
        $article->status           = $request->has('status') ? (bool) $request->status : false;

        // تحديث صورة المقال
        if ($request->hasFile('image')) {
            try {
                if ($article->image && $article->image !== 'default.webp') {
                    Storage::disk('public')->delete('images/articles/' . $article->image);
                }

                $article->image = $this->securelyStoreArticleImage($request->file('image'));
            } catch (\Exception $e) {
                Log::error('فشل في تحديث صورة المقال: ' . $e->getMessage());
            }
        }

        $article->save();

        // تحديث الكلمات المفتاحية
        if ($request->keywords) {
            $keywords = array_map('trim', explode(',', $request->keywords));

            $article->keywords()->detach();

            foreach ($keywords as $keyword) {
                if ($keyword === '') {
                    continue;
                }
                $keywordModel = Keyword::on($connection)->firstOrCreate(['keyword' => $keyword]);
                $article->keywords()->attach($keywordModel->id);
            }
        }

        // تحديث الملف المرفق (new_file) إن وجد
        if ($request->hasFile('new_file')) {
            try {
                $currentFile = $article->files->first();
                if ($currentFile) {
                    if (Storage::disk('public')->exists($currentFile->file_path)) {
                        Storage::disk('public')->delete($currentFile->file_path);
                    }
                    $currentFile->delete();
                }

                $schoolClass    = SchoolClass::on($connection)->find($request->class_id);
                $folderName     = $schoolClass ? $schoolClass->grade_name : 'General';
                $folderNameSlug = Str::slug($folderName);
                $folderCategory = Str::slug($request->file_category);
                $countrySlug    = Str::slug($countryParam);
                $folderPath     = "files/$countrySlug/$folderNameSlug/$folderCategory";

                $customFilename = $request->input('file_name');

                $fileInfo = $this->securelyStoreAttachment(
                    $request->file('new_file'),
                    $folderPath,
                    $customFilename
                );

                File::on($connection)->create([
                    'article_id'    => $article->id,
                    'file_path'     => $fileInfo['path'],
                    'file_type'     => $fileInfo['extension'],
                    'file_category' => $request->file_category,
                    'file_name'     => $fileInfo['filename'],
                    'file_size'     => $fileInfo['size'],
                    'mime_type'     => $fileInfo['mime_type']
                ]);
            } catch (\Exception $e) {
                Log::error('فشل في تحديث الملف المرفق: ' . $e->getMessage());
            }
        } else {
            // تحديث بيانات الملف الموجود (الفئة والاسم) إذا لم يتم رفع ملف جديد
            $currentFile = $article->files->first();
            if ($currentFile) {
                $shouldSave = false;
                if ($request->filled('file_category') && $request->file_category !== $currentFile->file_category) {
                    $currentFile->file_category = $request->file_category;
                    $shouldSave = true;
                }
                // تحديث اسم الملف فقط إذا تم إرساله
                if ($request->filled('file_name') && $request->file_name !== $currentFile->file_name) {
                    $currentFile->file_name = $request->file_name;
                    $shouldSave = true;
                }
                
                if ($shouldSave) {
                    $currentFile->save();
                }
            }
        }

        $this->forgetArticleCache(
            $connection,
            (int) $article->id,
            $previousSubjectId,
            $article->subject_id ? (int) $article->subject_id : null
        );

        return new ArticleResource($article->load(['schoolClass', 'subject', 'semester', 'keywords', 'files']));
    }

    /**
     * Download attached file and increment download count
     */
    public function download(Request $request, $id)
    {
        try {
            $countryParam = $request->input('database', $request->input('country_id', $request->input('country', '1')));
            $database     = $this->getConnection($countryParam);

            $file = File::on($database)->findOrFail($id);
            
            $file->increment('download_count');

            $filePath = storage_path('app/public/' . $file->file_path);
            
            if (file_exists($filePath)) {
                return response()->download($filePath, $file->file_name);
            }

            Log::error("File not found at path: {$filePath} for ID: {$id} on DB: {$database}");
            return response()->json(['message' => 'File not found on server'], 404);
        } catch (\Throwable $e) {
            Log::error('Error in ArticleApiController@download: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/articles/{id}
     * حذف المقال والملفات والصورة
     */
    public function destroy(Request $request, $id)
    {
        $country    = $request->input('country', '1');
        $connection = $this->getConnection($country);

        $article = Article::on($connection)->with('files')->findOrFail($id);

        // حذف صورة المقال
        $imageName = $article->image;
        if ($imageName && $imageName !== 'default.webp') {
            if (Storage::disk('public')->exists('images/articles/' . $imageName)) {
                Storage::disk('public')->delete('images/articles/' . $imageName);
            }
        }

        // حذف الملفات
        foreach ($article->files as $file) {
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }
            $file->delete();
        }

        $subjectId = $article->subject_id;
        $articleId = $article->id;

        $article->delete();

        // Clear cache
        $this->forgetArticleCache($connection, (int)$articleId, $subjectId ? (int)$subjectId : null, null);

        return new BaseResource([
            'message' => 'تم حذف المقال والصور والملفات المرتبطة به بنجاح.',
        ]);
    }

    /**
     * GET /api/articles/by-class/{grade_level}
     * إحضار المقالات حسب الصف
     */
    public function indexByClass(Request $request, $grade_level)
    {
        $countryParam = $request->input('database', $request->input('country_id', $request->input('country', '1')));
        $connection   = $this->getConnection($countryParam);

        $articles = Article::on($connection)
            ->whereHas('subject', function ($query) use ($grade_level) {
                $query->where('grade_level', $grade_level);
            })
            ->with(['subject', 'semester', 'schoolClass', 'keywords', 'files'])
            ->get();

        return (new ArticleCollection($articles))
            ->additional([
                'country' => $countryParam,
            ]);
    }

    /**
     * GET /api/articles/by-keyword/{keyword}
     * إحضار المقالات حسب كلمة مفتاحية
     */
    public function indexByKeyword(Request $request, $keyword)
    {
        $countryParam = $request->input('database', $request->input('country_id', $request->input('country', '1')));
        $connection   = $this->getConnection($countryParam);

        $keywordModel = Keyword::on($connection)->where('keyword', $keyword)->firstOrFail();
        $articles     = $keywordModel->articles()->with(['subject', 'semester', 'schoolClass', 'keywords', 'files'])->get();

        return (new ArticleCollection($articles))
            ->additional([
                'country' => $countryParam,
                'keyword' => $keywordModel,
            ]);
    }

    /**
     * POST /api/articles/{id}/publish
     */
    public function publish(Request $request, $id)
    {
        try {
            $countryParam = $request->input('database', $request->input('country_id', $request->input('country', 1)));
            $connection   = $this->getConnection($countryParam);

            $article = Article::on($connection)->findOrFail($id);
            $article->status = true;
            $article->save();

            // Clear cache
            $this->forgetArticleCache($connection, (int)$id, $article->subject_id ? (int)$article->subject_id : null, null);

            return (new ArticleResource($article))
                ->additional([
                    'message' => __('Article published successfully'),
                ]);
        } catch (\Exception $e) {
            Log::error('Error publishing article: ' . $e->getMessage());

            return (new BaseResource(['message' => __('Failed to publish article')]))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * POST /api/articles/{id}/unpublish
     */
    public function unpublish(Request $request, $id)
    {
        try {
            $countryParam = $request->input('database', $request->input('country_id', $request->input('country', 1)));
            $connection   = $this->getConnection($countryParam);

            $article = Article::on($connection)->findOrFail($id);
            $article->status = false;
            $article->save();

            // Clear cache
            $this->forgetArticleCache($connection, (int)$id, $article->subject_id ? (int)$article->subject_id : null, null);

            return (new ArticleResource($article))
                ->additional([
                    'message' => __('Article unpublished successfully'),
                ]);
        } catch (\Exception $e) {
            Log::error('Error unpublishing article: ' . $e->getMessage());

            return (new BaseResource(['message' => __('Failed to unpublish article')]))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * إرسال إشعار بعد إنشاء المقال
     */
    protected function sendNotification(Article $article): void
    {
        if (!config('onesignal.enabled')) {
            Log::info('OneSignal is disabled via config. Skipping ArticleApiController::sendNotification');
            return;
        }

        try {
            $className = SchoolClass::on($article->getConnectionName())->find($article->grade_level)?->grade_name ?? '';
            $title     = "تم نشر مقال جديد: {$article->title}";
            if ($className) {
                $title .= " (الصف: {$className})";
            }

            $this->oneSignalService->sendNotification($title, $article->title);
        } catch (\Exception $e) {
            Log::warning('Failed to send notification: ' . $e->getMessage());
        }
    }

    /**
     * استبدال الكلمات المفتاحية بروابط
     * (Deprecated: Use the one defined near show() or keep this one and remove the other.
     * Actually, I should remove this one as I defined a better one above.)
     */


    /**
     * تخزين مرفق بشكل آمن
     */
    private function securelyStoreAttachment($file, string $folderPath, ?string $filename = null): array
    {
        try {
            $originalFilename = $file->getClientOriginalName();
            $finalFilename    = $filename ?: $originalFilename;

            $path = $this->secureFileUploadService->securelyStoreFile(
                $file,
                $folderPath,
                false,
                $finalFilename
            );

            return [
                'path'      => $path,
                'filename'  => $finalFilename,
                'size'      => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
            ];
        } catch (\Exception $e) {
            Log::error('فشل في تخزين الملف المرفق: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * تخزين صورة المقال بشكل آمن وتحويلها WebP
     */
    private function securelyStoreArticleImage($file): string
    {
        try {
            return $this->secureFileUploadService->securelyStoreFile(
                $file,
                'images/articles',
                true
            );
        } catch (\Exception $e) {
            Log::error('فشل في تخزين صورة المقال: ' . $e->getMessage());
            return 'default.webp';
        }
    }
}
