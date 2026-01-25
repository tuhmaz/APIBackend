<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Article;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Resources\Api\FileResource;
use App\Http\Resources\BaseResource;

class FileApiController extends Controller
{
    private function getConnection(string $country): string
    {
        return match ($country) {
            '1', 'jo', 'jordan' => 'jo',
            '2', 'sa', 'saudi' => 'sa',
            '3', 'eg', 'egypt' => 'eg',
            '4', 'ps', 'palestine' => 'ps',
            default => 'jo',
        };
    }

    /**
     * GET /api/files
     * قائمة الملفات مع فلاتر
     */
    public function index(Request $request)
    {
        $country = $request->input('country', '1');
        $connection = $this->getConnection($country);

        $query = File::on($connection)->with('article');

        if ($request->filled('category')) {
            $cat = $request->category;
            if ($cat === 'images') {
                $query->where('mime_type', 'like', 'image/%');
            } else {
                $categoryMap = [
                    'study_plan' => ['plans', 'study-plan', 'study_plan'],
                    'worksheet' => ['worksheet', 'papers', 'worksheets'],
                    'exam' => ['tests', 'exams', 'test', 'exam'],
                    'book' => ['books', 'book'],
                    'record' => ['records', 'record'],
                ];

                if (array_key_exists($cat, $categoryMap)) {
                    $query->whereIn('file_category', $categoryMap[$cat]);
                } else {
                    $query->where('file_category', $cat);
                }
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('article', function ($q) use ($search) {
                $q->where('title', 'like', "%$search%");
            });
        }

        $files = $query->latest()->paginate($request->per_page ?? 20);

        return FileResource::collection($files)
            ->additional([
                'success' => true,
            ]);
    }

    /**
     * POST /api/files
     * رفع ملف جديد
     */
    public function store(Request $request)
    {
        $request->validate([
            'country' => 'required',
            'article_id' => 'required',
            'file_category' => 'required|string',
            'file' => 'required|file'
        ]);

        $connection = $this->getConnection($request->country);

        $article = Article::on($connection)->findOrFail($request->article_id);

        $className = Str::slug($article->schoolClass->grade_name);
        $countrySlug = Str::slug($request->country);
        $categorySlug = Str::slug($request->file_category);

        $file = $request->file('file');
        $original = $file->getClientOriginalName();
        $safe = Str::slug(pathinfo($original, PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();

        $relativePath = "files/$countrySlug/$className/$categorySlug/$safe";

        Storage::disk('public')->put($relativePath, file_get_contents($file));

        $fileModel = File::on($connection)->create([
            'article_id' => $request->article_id,
            'file_path' => $relativePath,
            'file_name' => $original,
            'file_type' => $file->getClientOriginalExtension(),
            'file_category' => $request->file_category,
        ]);

        return (new FileResource($fileModel))
            ->additional([
                'message' => 'File uploaded successfully',
            ]);
    }

    /**
     * GET /api/files/{id}
     * عرض ملف واحد
     */
    public function show(Request $request, $id)
    {
        $connection = $this->getConnection($request->country ?? '1');

        $file = File::on($connection)
            ->with('article')
            ->findOrFail($id);

        return new FileResource($file);
    }

    /**
     * GET /api/files/{id}/info
     * جلب معلومات الملف مع المقال أو المنشور المرتبط (للصفحة التحميل)
     */
    public function info(Request $request, $id)
    {
        $database = $request->input('database', 'jo');

        $file = File::on($database)
            ->with(['article.subject', 'article.schoolClass', 'post.category'])
            ->findOrFail($id);

        $response = [
            'file' => [
                'id' => $file->id,
                'file_name' => $file->file_name,
                'file_path' => $file->file_path,
                'file_url' => Storage::url($file->file_path),
                'file_type' => $file->file_type,
                'file_size' => $file->file_size,
                'mime_type' => $file->mime_type,
                'category' => $file->file_category,
                'download_count' => $file->download_count ?? 0,
                'views_count' => $file->views_count ?? 0,
            ],
            'type' => null,
            'item' => null,
        ];

        // تحديد نوع المحتوى المرتبط (مقال أو منشور)
        if ($file->article_id && $file->article) {
            $response['type'] = 'article';
            $response['item'] = [
                'id' => $file->article->id,
                'title' => $file->article->title,
                'meta_description' => $file->article->meta_description,
                'image_url' => $file->article->image_url,
                'subject' => $file->article->subject ? [
                    'id' => $file->article->subject->id,
                    'name' => $file->article->subject->name ?? $file->article->subject->subject_name,
                ] : null,
                'schoolClass' => $file->article->schoolClass ? [
                    'id' => $file->article->schoolClass->id,
                    'grade_name' => $file->article->schoolClass->grade_name,
                ] : null,
            ];
        } elseif ($file->post_id && $file->post) {
            $response['type'] = 'post';
            $response['item'] = [
                'id' => $file->post->id,
                'title' => $file->post->title,
                'meta_description' => $file->post->meta_description ?? $file->post->excerpt,
                'image_url' => $file->post->image_url ?? $file->post->featured_image,
                'category' => $file->post->category ? [
                    'id' => $file->post->category->id,
                    'name' => $file->post->category->name,
                ] : null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $response,
        ]);
    }

    /**
     * POST /api/files/{id}/increment-view
     * Increment file views_count (deduped per visitor for a short window)
     */
    public function incrementView(Request $request, $id)
    {
        $databaseParam = (string) $request->input('database', $request->input('country', 'jo'));
        $database = $this->getConnection($databaseParam);

        $file = File::on($database)->findOrFail($id);

        $viewerId = null;
        if ($request->hasSession()) {
            $viewerId = $request->session()->getId();
        }
        if (!$viewerId) {
            $viewerId = $request->cookie('visitor_id') ?: $request->ip();
        }

        $viewerHash = sha1((string) $viewerId);
        $cacheKey = "file_viewed:{$database}:{$id}:{$viewerHash}";
        $ttlMinutes = 30;

        if (Cache::add($cacheKey, 1, now()->addMinutes($ttlMinutes))) {
            $file->increment('views_count');
        }

        $file->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'views_count' => $file->views_count ?? 0,
                'download_count' => $file->download_count ?? 0,
            ],
        ]);
    }

    /**
     * GET /api/files/{id}/download
     * تحميل ملف
     */
    public function download(Request $request, $id)
    {
        $connection = $this->getConnection($request->country ?? '1');

        $file = File::on($connection)->findOrFail($id);

        $path = storage_path("app/public/" . $file->file_path);

        if (!file_exists($path)) {
            return (new BaseResource(['message' => 'File not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        $file->increment('download_count');

        return response()->download($path, $file->file_name);
    }

    /**
     * PUT /api/files/{id}
     * تحديث ملف
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'country' => 'required',
            'file_category' => 'required|string',
            'article_id' => 'required',
            'file' => 'nullable|file'
        ]);

        $connection = $this->getConnection($request->country);
        $file = File::on($connection)->findOrFail($id);

        // تحديث الخصائص
        $file->file_category = $request->file_category;
        $file->article_id = $request->article_id;

        if ($request->hasFile('file')) {
            // حذف القديم
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }

            $uploaded = $request->file('file');
            $newName = time() . '_' . Str::slug(pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME))
                . '.' . $uploaded->getClientOriginalExtension();

            $relativePath = "files/updates/$newName";
            Storage::disk('public')->put($relativePath, file_get_contents($uploaded));

            $file->file_name = $uploaded->getClientOriginalName();
            $file->file_type = $uploaded->getClientOriginalExtension();
            $file->file_path = $relativePath;
        }

        $file->save();

        return (new FileResource($file))
            ->additional([
                'message' => 'File updated successfully',
            ]);
    }

    /**
     * DELETE /api/files/{id}
     */
    public function destroy(Request $request, $id)
    {
        $connection = $this->getConnection($request->country ?? '1');

        $file = File::on($connection)->findOrFail($id);

        if (Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete();

        return new BaseResource([
            'message' => 'File deleted successfully'
        ]);
    }
}
