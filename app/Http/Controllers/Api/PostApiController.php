<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Post;
use App\Models\File;
use App\Models\Category;
use App\Models\Keyword;
use App\Models\User;
use App\Notifications\PostNotification;
use App\Services\SecureFileUploadService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Api\PostResource;
use App\Http\Resources\BaseResource;

class PostApiController extends Controller
{
    protected $uploadService;

    public function __construct(SecureFileUploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /** ------------------------------
     *  Helper: Resolve country DB
     * ------------------------------ */
    private function connection(string $country): string
    {
        return match ($country) {
            '1','jordan','jo'    => 'jo',
            '2','sa','saudi'     => 'sa',
            '3','egypt','eg'     => 'eg',
            '4','palestine','ps' => 'ps',
            default => throw new NotFoundHttpException("Invalid country"),
        };
    }

    /** ------------------------------
     *  GET /api/posts
     * ------------------------------ */
    public function index(Request $request)
    {
        $country = $request->country ?? '1';
        $db = $this->connection($country);

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSortColumns = ['created_at', 'id', 'views', 'title'];
        if (!in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'created_at';
        }

        // Build cache key based on all parameters
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 10);
        $cacheKey = "posts_list_{$db}_" . md5(json_encode([
            'search' => $request->input('search'),
            'category_id' => $request->input('category_id'),
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
            'page' => $page,
            'per_page' => $perPage,
        ]));

        $posts = Cache::remember($cacheKey, 60, function () use ($db, $request, $sortBy, $sortDir, $perPage) {
            return Post::on($db)
                ->with(['category', 'author'])
                ->when($request->input('search'), fn($q, $search) =>
                    $q->where('title', 'like', "%$search%")
                      ->orWhere('content', 'like', "%$search%"))
                ->when($request->input('category_id'), fn($q, $id) =>
                    $q->where('category_id', $id))
                ->orderBy($sortBy, $sortDir)
                ->paginate($perPage);
        });

        return PostResource::collection($posts)
            ->additional([
                'success' => true,
                'country' => $country,
            ]);
    }

    /** ------------------------------
     *  GET /api/posts/{id}
     * ------------------------------ */
    public function show(Request $request, $id)
    {
        $country = $request->country ?? '1';
        $db = $this->connection($country);

        // Use cache for individual post
        $cacheKey = "post_{$db}_{$id}";
        $post = Cache::remember($cacheKey, 600, function () use ($db, $id) {
            return Post::on($db)
                ->with(['attachments', 'category', 'author'])
                ->findOrFail($id);
        });

        return new PostResource($post);
    }

    /** ------------------------------
     *  POST /api/posts/{id}/increment-view
     * ------------------------------ */
    public function incrementView(Request $request, $id)
    {
        $country = $request->country ?? '1';
        $db = $this->connection($country);

        $post = Post::on($db)->findOrFail($id);
        $post->increment('views');

        return response()->json([
            'success' => true,
            'message' => 'View count incremented',
            'views' => $post->views
        ]);
    }

    /** ------------------------------
     *  POST /api/posts/{id}/toggle-status
     * ------------------------------ */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $country = (string) $request->input('country', '1');
            
            // Log the request for debugging
            Log::info("Toggle Status Request: ID={$id}, Country={$country}");

            $db = $this->connection($country);

            $post = Post::on($db)->findOrFail($id);
            $post->is_active = !$post->is_active;
            $post->save();

            // Clear cache for this post
            Cache::forget("post_{$db}_{$id}");

            return response()->json([
                'success' => true,
                'message' => 'Post status updated successfully',
                'is_active' => $post->is_active
            ]);
        } catch (\Exception $e) {
            Log::error("Toggle Status Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /** ------------------------------
     *  POST /api/posts
     * ------------------------------ */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'country'      => 'required|string',
            'title'        => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    try {
                        $country = $request->input('country');
                        $db = $this->connection($country);
                        if (Post::on($db)->where('title', $value)->exists()) {
                            $fail('عنوان المنشور موجود مسبقاً، يرجى اختيار عنوان آخر.');
                        }
                    } catch (\Exception $e) {
                        // Ignore connection errors here, let country validation handle it
                    }
                }
            ],
            'content'      => 'required|string',
            'category_id'  => 'required|integer',
            'meta_description' => 'nullable|string|max:255',
            'keywords'     => 'nullable|string|max:255',
            'is_active'    => 'boolean',
            'is_featured'  => 'boolean',
            'image'        => 'nullable|file|mimes:jpg,jpeg,png,webp|max:40960',
            'alt'          => 'nullable|string|max:255',
            'attachments.*'=> 'file|max:40960'
        ]);

        $db = $this->connection($validated['country']);

        DB::connection($db)->beginTransaction();

        try {

            /** --- Upload Featured Image --- */
            $imagePath = 'posts/default_post_image.jpg';
            if ($request->hasFile('image')) {
                $imagePath = $this->uploadService->securelyStoreFile(
                    $request->file('image'),
                    'images/posts',
                    true
                );
            }

            /** --- Create Slug --- */
            $slug = Str::slug($validated['title']) . '-' . time();

            /** --- Create Post --- */
            $post = new Post();
            $post->setConnection($db);
            $post->title = $validated['title'];
            $post->content = $validated['content'];
            $post->slug = $slug;
            $post->category_id = $validated['category_id'];
            $post->meta_description = $validated['meta_description'] 
                ?: Str::limit(strip_tags($validated['content']), 160);
            $post->keywords = $validated['keywords'] ?? '';
            $post->image = $imagePath;
            $post->alt = $request->input('alt') ?: $validated['title'];
            $post->is_active = $request->boolean('is_active', true);
            $post->is_featured = $request->boolean('is_featured', false);
            $post->country = $validated['country'];
            $post->author_id = Auth::id();
            $post->save();

            /** --- Attach Keywords --- */
            if (!empty($post->keywords)) {
                foreach (array_map('trim', explode(',', $post->keywords)) as $kw) {
                    if ($kw === '') continue;
                    $kwModel = Keyword::on($db)->firstOrCreate(['keyword' => $kw]);
                    $post->keywords()->syncWithoutDetaching([$kwModel->id]);
                }
            }

            /** --- Attach Files --- */
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $fileInfo = $this->uploadService->securelyStoreFile($file, 'files/posts', false);

                    File::on($db)->create([
                        'post_id' => $post->id,
                        'file_path' => $fileInfo,
                        'file_type' => $file->extension(),
                        'file_category' => 'attachment',
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ]);
                }
            }

            /** --- Send Notifications --- */
            try {
                // Notify all users about the new post
                // We use chunk to handle large number of users efficiently
                User::select('id')->chunk(100, function ($users) use ($post) {
                    foreach ($users as $user) {
                        $user->notify(new PostNotification($post));
                    }
                });
            } catch (\Exception $e) {
                Log::error("Failed to send post notifications: " . $e->getMessage());
                // Don't fail the request just because notifications failed
            }

            DB::connection($db)->commit();

            return (new PostResource($post))
                ->additional([
                    'message' => 'Post created successfully',
                ]);

        } catch (\Exception $e) {
            DB::connection($db)->rollBack();
            Log::error("API Post Create Failed: " . $e->getMessage());
            return (new BaseResource(['message' => 'Failed to create post', 'error' => $e->getMessage()]))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /** ------------------------------
     *  PUT /api/posts/{id}
     * ------------------------------ */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'country'      => 'required|string',
            'title'        => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request, $id) {
                    try {
                        $country = $request->input('country');
                        $db = $this->connection($country);
                        if (Post::on($db)->where('title', $value)->where('id', '!=', $id)->exists()) {
                            $fail('عنوان المنشور موجود مسبقاً، يرجى اختيار عنوان آخر.');
                        }
                    } catch (\Exception $e) {
                        // Ignore connection errors here
                    }
                }
            ],
            'content'      => 'required|string',
            'category_id'  => 'required|integer',
            'meta_description' => 'nullable|string|max:255',
            'keywords'     => 'nullable|string|max:255',
            'is_active'    => 'boolean',
            'is_featured'  => 'boolean',
            'image'        => 'nullable|file|mimes:jpg,jpeg,png,webp|max:40960',
            'alt'          => 'nullable|string|max:255',
            'attachments.*'=> 'file|max:40960'
        ]);

        $db = $this->connection($validated['country']);

        try {
            $post = Post::on($db)->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Post not found in the selected country'], 404);
        }

        DB::connection($db)->beginTransaction();

        try {
            Log::info("Updating post {$id} in {$validated['country']}", ['data' => $validated, 'auth_id' => Auth::id()]);

            /** --- Update image if new uploaded --- */
            if ($request->hasFile('image')) {
                Log::info("Updating image for post {$id}");
                Storage::disk('public')->delete($post->image);
                $post->image = $this->uploadService->securelyStoreFile(
                    $request->file('image'),
                    'images/posts',
                    true
                );
            }

            $post->title = $validated['title'];
            $post->content = $validated['content'];
            $post->category_id = $validated['category_id'];
            $post->meta_description = $validated['meta_description'] 
                ?: Str::limit(strip_tags($validated['content']), 160);
            $post->keywords = $validated['keywords'] ?? '';
            $post->is_active = $request->boolean('is_active', true);
            $post->is_featured = $request->boolean('is_featured', false);
            if ($request->filled('alt')) {
                $post->alt = $request->input('alt');
            }
            // $post->author_id = Auth::id(); // Disable author update on edit to prevent issues/overwrite
            
            $post->save();
            Log::info("Post {$id} saved successfully");

            // Clear cache for this post
            Cache::forget("post_{$db}_{$id}");

            /** --- Refresh Keywords --- */
            $post->keywords()->detach();
            if (!empty($post->keywords)) {
                $keywordsList = array_map('trim', explode(',', $post->keywords));
                Log::info("Syncing keywords for post {$id}", ['keywords' => $keywordsList]);
                foreach ($keywordsList as $kw) {
                    if ($kw !== '') {
                        $kwModel = Keyword::on($db)->firstOrCreate(['keyword' => $kw]);
                        $post->keywords()->syncWithoutDetaching([$kwModel->id]);
                    }
                }
            }

            /** --- New Attachments --- */
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $fileInfo = $this->uploadService->securelyStoreFile($file, 'files/posts', false);

                    File::on($db)->create([
                        'post_id' => $post->id,
                        'file_path' => $fileInfo,
                        'file_type' => $file->extension(),
                        'file_category' => 'attachment',
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ]);
                }
            }

            DB::connection($db)->commit();

            return (new PostResource($post))
                ->additional([
                    'message' => 'Post updated successfully',
                ]);

        } catch (\Exception $e) {
            DB::connection($db)->rollBack();
            Log::error("API Post Update Failed: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return (new BaseResource(['message' => 'Failed to update post', 'error' => $e->getMessage()]))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /** ------------------------------
     *  DELETE /api/posts/{id}
     * ------------------------------ */
    public function destroy(Request $request, $id)
    {
        $country = $request->country ?? '1';
        $db = $this->connection($country);

        $post = Post::on($db)->findOrFail($id);

        DB::connection($db)->beginTransaction();

        try {
            Storage::disk('public')->delete($post->image);
            $post->attachments()->delete();
            $post->delete();

            DB::connection($db)->commit();

            return new BaseResource([
                'message' => 'Post deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::connection($db)->rollBack();
            return (new BaseResource(['message' => 'Failed to delete post', 'error' => $e->getMessage()]))
                ->response($request)
                ->setStatusCode(500);
        }
    }

}
