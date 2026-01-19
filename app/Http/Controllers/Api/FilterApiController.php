<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Semester;
use App\Models\File;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\BaseResource;

class FilterApiController extends Controller
{
    private function getConnection(Request $request): string
    {
        return $request->query('database', session('database', 'jo'));
    }

    private function resolveFileCategories(?string $category): array
    {
        if (!$category) {
            return [];
        }

        $categoryMap = [
            'study_plan' => ['study_plan', 'plans', 'study-plan'],
            'plans' => ['study_plan', 'plans', 'study-plan'],
            'worksheet' => ['worksheet', 'papers', 'worksheets'],
            'papers' => ['worksheet', 'papers', 'worksheets'],
            'exam' => ['exam', 'tests', 'exams', 'test'],
            'tests' => ['exam', 'tests', 'exams', 'test'],
            'book' => ['book', 'books'],
            'books' => ['book', 'books'],
            'record' => ['record', 'records'],
            'records' => ['record', 'records'],
        ];

        return $categoryMap[$category] ?? [$category];
    }

    private function remember(string $cacheKey, int $ttl, \Closure $callback, ?string $database = null)
    {
        // If database is provided, include cache version for invalidation
        if ($database) {
            $cacheVersion = Cache::get("filter_cache_version_{$database}", 0);
            $cacheKey = "{$cacheKey}_v{$cacheVersion}";
        }
        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * GET /api/filter
     * إرجاع نتائج المقالات والملفات بناءً على الفلاتر
     */
    public function index(Request $request)
    {
        $database = $this->getConnection($request);

        // Use cache for classes
        $cacheKey = "school_classes_{$database}";
        $classes = $this->remember($cacheKey, 600, function () use ($database) {
            return SchoolClass::on($database)->get();
        });

        // Build cache key for articles based on filters
        $page = $request->get('page', 1);
        $perPageArticles = max(6, min((int)$request->get('per_page_articles', 12), 48));
        $articlesCacheKey = "filter_articles_{$database}_" . md5(json_encode([
            'semester_id' => $request->semester_id,
            'class_id' => $request->class_id,
            'subject_id' => $request->subject_id,
            'file_category' => $request->file_category,
            'page' => $page,
            'per_page' => $perPageArticles,
        ]));

        $articles = $this->remember($articlesCacheKey, 30, function () use ($database, $request, $perPageArticles) {
            $articleQuery = Article::on($database)->where('status', 1);

            if ($request->semester_id) {
                $articleQuery->where('semester_id', $request->semester_id);
            }

            if ($request->class_id) {
                $articleQuery->where('grade_level', $request->class_id);
            }

            if ($request->subject_id) {
                $articleQuery->where('subject_id', $request->subject_id);
            }

            if ($request->file_category) {
                $fileCategories = $this->resolveFileCategories($request->file_category);
                $articleQuery->whereHas('files', function ($q) use ($fileCategories) {
                    $q->whereIn('file_category', $fileCategories);
                });
            }

            return $articleQuery
                ->with(['subject', 'semester', 'schoolClass', 'files'])
                ->latest('id')
                ->paginate($perPageArticles);
        }, $database);

        // إعداد استعلام الملفات - للمقالات المنشورة فقط
        $files = [];
        if (!$request->has('only_articles') && !$request->has('articles_only')) {
            $perPageFiles = max(6, min((int)$request->get('per_page_files', 12), 48));
            $filesCacheKey = "filter_files_{$database}_" . md5(json_encode([
                'semester_id' => $request->semester_id,
                'class_id' => $request->class_id,
                'subject_id' => $request->subject_id,
                'file_category' => $request->file_category,
                'page' => $request->get('page_files', 1),
                'per_page' => $perPageFiles,
            ]));

            $files = $this->remember($filesCacheKey, 30, function () use ($database, $request, $perPageFiles) {
                $fileQuery = File::on($database)
                    ->when($request->file_category, fn($q) => $q->whereIn('file_category', $this->resolveFileCategories($request->file_category)))
                    ->whereHas('article', function ($q) use ($request) {
                        $q->where('status', 1);

                        if ($request->semester_id) {
                            $q->where('semester_id', $request->semester_id);
                        }
                        if ($request->class_id) {
                            $q->where('grade_level', $request->class_id);
                        }
                        if ($request->subject_id) {
                            $q->where('subject_id', $request->subject_id);
                        }
                    });

                return $fileQuery
                    ->with(['article.subject', 'article.semester'])
                    ->latest('id')
                    ->paginate($perPageFiles);
            }, $database);
        }

        return new BaseResource([
            'database' => $database,
            'filters' => [
                'class_id' => $request->class_id,
                'subject_id' => $request->subject_id,
                'semester_id' => $request->semester_id,
                'file_category' => $request->file_category,
            ],
            'classes' => $classes,
            'articles' => $articles,
            'files' => $files
        ]);
    }

    /**
     * GET /api/filter/subjects/{classId}
     */
    public function getSubjectsByClass(Request $request, $classId)
    {
        $database = $this->getConnection($request);

        // Use cache for school class
        $classCacheKey = "school_class_{$database}_{$classId}";
        $schoolClass = $this->remember($classCacheKey, 600, function () use ($database, $classId) {
            return SchoolClass::on($database)->find($classId);
        });

        if (!$schoolClass) {
            return (new BaseResource(['message' => 'Class not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        // Use cache for subjects
        $subjectsCacheKey = "subjects_class_{$database}_{$schoolClass->grade_level}";
        $subjects = $this->remember($subjectsCacheKey, 300, function () use ($database, $schoolClass) {
            return Subject::on($database)
                ->where('grade_level', $schoolClass->grade_level)
                ->get();
        });

        return new BaseResource([
            'subjects' => $subjects
        ]);
    }

    /**
     * GET /api/filter/semesters/{subjectId}
     */
    public function getSemestersBySubject(Request $request, $subjectId)
    {
        $database = $this->getConnection($request);

        // Use cache for subject
        $subjectCacheKey = "subject_{$database}_{$subjectId}";
        $subject = $this->remember($subjectCacheKey, 600, function () use ($database, $subjectId) {
            return Subject::on($database)->find($subjectId);
        });

        if (!$subject) {
            return (new BaseResource(['message' => 'Subject not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        // Use cache for semesters
        $semestersCacheKey = "semesters_subject_{$database}_{$subject->grade_level}";
        $semesters = $this->remember($semestersCacheKey, 300, function () use ($database, $subject) {
            return Semester::on($database)
                ->where('grade_level', $subject->grade_level)
                ->get();
        });

        // Get the SchoolClass ID for the subject's grade_level
        // This is needed because Article.grade_level references school_classes.id, not grade_level
        $classIdCacheKey = "school_class_id_{$database}_{$subject->grade_level}";
        $schoolClass = $this->remember($classIdCacheKey, 600, function () use ($database, $subject) {
            return SchoolClass::on($database)
                ->where('grade_level', $subject->grade_level)
                ->first();
        });

        return new BaseResource([
            'subject' => $subject,
            'semesters' => $semesters,
            'class_id' => $schoolClass?->id, // Add class_id to response
        ]);
    }

    /**
     * GET /api/filter/file-types/{semesterId}
     */
    public function getFileTypesBySemester(Request $request, $semesterId)
    {
        $database = $this->getConnection($request);

        $fileTypes = File::on($database)
            ->whereHas('article.semester', function ($q) use ($semesterId) {
                $q->where('id', $semesterId);
            })
            ->distinct()
            ->pluck('file_category');
            
        // dd($fileTypes);

        return new BaseResource([
            'file_types' => $fileTypes
        ]);
    }
}
