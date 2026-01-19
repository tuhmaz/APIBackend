<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Semester;
use App\Models\File;
use App\Models\Article;
use Illuminate\Http\Request;

class FilterController extends Controller
{
    // دالة لاسترجاع قاعدة البيانات المناسبة
    private function getConnection(Request $request): string
    {
        return $request->query('database', session('database', 'jo'));
    }

    // عرض صفحة الفلترة
    public function index(Request $request)
    {
        // استعادة قاعدة البيانات المحددة
        $database = $this->getConnection($request);

        // جلب الصفوف من قاعدة البيانات المناسبة
        $classes = SchoolClass::on($database)->get();

        // تجهيز الاستعلام لجلب المقالات بناءً على التصفية
        $articleQuery = Article::on($database);

        // تصفية بناءً على الصف
        if ($request->class_id) {
            $articleQuery->whereHas('semester', function ($q) use ($request) {
                $q->where('grade_level', $request->class_id);
            });
        }

        // تصفية بناءً على المادة
        if ($request->subject_id) {
            $articleQuery->where('subject_id', $request->subject_id);
        }

        // تصفية بناءً على الفصل الدراسي
        if ($request->semester_id) {
            $articleQuery->where('semester_id', $request->semester_id);
        }

        // تصفية بناءً على نوع الملف وجلب المقالات المرتبطة
        if ($request->file_category) {
            $articleQuery->whereHas('files', function ($q) use ($request) {
                $q->where('file_category', $request->file_category);
            });
        }

        // إعداد القيم الافتراضية للترقيم
        $perPageArticles = max(6, min((int) $request->get('per_page_articles', 12), 48));
        $perPageFiles = max(6, min((int) $request->get('per_page_files', 12), 48));

        // جلب المقالات المصفاة مع الترقيم
        $articles = $articleQuery
            ->latest('id')
            ->paginate($perPageArticles, ['*'], 'page_articles')
            ->withQueryString();
        $articles->fragment('articles');

        // تجهيز استعلام الملفات بناءً على نفس فلاتر المقالات عبر العلاقة
        $fileQuery = File::on($database)
            ->when($request->file_category, fn($q) => $q->where('file_category', $request->file_category))
            ->whereHas('article', function ($q) use ($request) {
                if ($request->class_id) {
                    $q->whereHas('semester', function ($qq) use ($request) {
                        $qq->where('grade_level', $request->class_id);
                    });
                }
                if ($request->subject_id) {
                    $q->where('subject_id', $request->subject_id);
                }
                if ($request->semester_id) {
                    $q->where('semester_id', $request->semester_id);
                }
            });

        // جلب الملفات مع الترقيم المستقل
        $files = $fileQuery
            ->latest('id')
            ->paginate($perPageFiles, ['*'], 'page_files')
            ->withQueryString();
        $files->fragment('files');

        // تمرير المتغيرات إلى العرض
        return view('content.frontend.filter-files', compact('classes', 'articles', 'files', 'database', 'perPageArticles', 'perPageFiles'));
    }

    // جلب المواد بناءً على الصف
    public function getSubjectsByClass(Request $request, $classId)
    {
        $database = $this->getConnection($request);

        // جلب الصف المحدد
        $schoolClass = SchoolClass::on($database)->find($classId);

        if (!$schoolClass) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        // جلب المواد التي تنتمي إلى الصف المحدد
        $subjects = Subject::on($database)->where('grade_level', $schoolClass->grade_level)->get();

        if ($subjects->isEmpty()) {
            return response()->json(['message' => 'No subjects found'], 404);
        }

        return response()->json($subjects);
    }

    // جلب الفصول بناءً على المادة
    public function getSemestersBySubject(Request $request, $subjectId)
    {
        $database = $this->getConnection($request);

        // جلب المادة المحددة
        $subject = Subject::on($database)->find($subjectId);

        if (!$subject) {
            return response()->json(['message' => 'Subject not found'], 404);
        }

        // جلب الفصول الدراسية التي تنتمي إلى المادة
        $semesters = Semester::on($database)->where('grade_level', $subject->grade_level)->get();

        if ($semesters->isEmpty()) {
            return response()->json(['message' => 'No semesters found'], 404);
        }

        return response()->json($semesters);
    }

    // جلب أنواع الملفات بناءً على الفصل
    public function getFileTypesBySemester(Request $request, $semesterId)
    {
        $database = $this->getConnection($request);

        // جلب أنواع الملفات بناءً على الفصل الدراسي المحدد
        $fileTypes = File::on($database)
            ->whereHas('article.semester', function ($q) use ($semesterId) {
                $q->where('id', $semesterId);
            })
            ->distinct()
            ->pluck('file_type'); // جلب الأنواع الفريدة من الملفات

        return response()->json($fileTypes);
    }
}
