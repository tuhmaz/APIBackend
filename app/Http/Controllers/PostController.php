<?php

namespace App\Http\Controllers;

use App\Services\SecureFileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;
use App\Models\Category;
use App\Models\Keyword;
use App\Models\File;
use App\Jobs\GenerateSitemapJob;


class PostController extends NewsController
{
  private array $countries = [
    '1' => 'الأردن',
    '2' => 'السعودية',
    '3' => 'مصر',
    '4' => 'فلسطين'
];

/**
 * خدمة التحميل الآمن للملفات
 */
protected $secureFileUploadService;

/**
 * إنشاء مثيل جديد من وحدة التحكم.
 */
public function __construct(SecureFileUploadService $secureFileUploadService)
{
    $this->secureFileUploadService = $secureFileUploadService;
    $this->middleware('auth');
}

/**
 * تخزين الملف المرفق بشكل آمن للبوست
 */
private function securelyStoreAttachment($file, $folderPath, $filename = null): array
{
    try {
        $originalFilename = $file->getClientOriginalName();
        $finalFilename = $filename ?: $originalFilename;

        $path = $this->secureFileUploadService->securelyStoreFile(
            $file,
            $folderPath,
            false
        );

        return [
            'path' => $path,
            'filename' => $finalFilename,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
        ];
    } catch (\Exception $e) {
        Log::error('فشل في تخزين مرفق البوست: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * حذف مرفق مرتبط بالبوست
 */
public function destroyAttachment(Request $request, $postId, $fileId)
{
    $country = $request->input('country', '1');
    $connection = $this->getConnection($country);

    $post = Post::on($connection)->findOrFail($postId);
    $file = File::on($connection)->findOrFail($fileId);

    if ($file->post_id !== $post->id) {
        abort(403);
    }

    DB::connection($connection)->beginTransaction();
    try {
        if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }
        $file->delete();
        DB::connection($connection)->commit();
        return back()->with('success', __('Attachment deleted successfully'));
    } catch (\Exception $e) {
        DB::connection($connection)->rollBack();
        return back()->with('error', __('Failed to delete attachment'));
    }
}

/**
 * Normalize WYSIWYG HTML content before saving.
 * - Replace non-breaking spaces with normal spaces
 * - Remove empty paragraphs and excessive whitespace
 */
private function normalizeContentHtml(string $html): string
{
    // Replace &nbsp; with a regular space
    $normalized = preg_replace('/\s*&nbsp;\s*/u', ' ', $html);

    // Remove paragraphs that are effectively empty (e.g., <p>\s*</p> or <p><br></p>)
    $normalized = preg_replace('/<p>(\s|&nbsp;|<br\s*\/?\s*>)*<\/p>/iu', '', $normalized);

    // Collapse multiple spaces
    $normalized = preg_replace('/\s{2,}/u', ' ', $normalized);

    return trim($normalized);
}

/**
 * Create a meta description from either provided text or generated from content.
 * - If provided meta is empty or whitespace, generate from content (strip HTML, collapse spaces) to ~160 chars.
 * - Ensure result does not exceed 255 chars per validation rule.
 */
private function makeMetaDescription(?string $meta, string $content): string
{
    $provided = trim((string) $meta);
    if ($provided !== '') {
        // Normalize provided meta to a single line and limit to ~160
        $plain = strip_tags($provided);
        $decoded = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = preg_replace('/\x{00A0}|&nbsp;/u', ' ', $decoded);
        $clean = trim(preg_replace('/\s+/u', ' ', $decoded));
        return Str::limit($clean, 160, '');
    }

    // Strip tags then decode HTML entities (e.g., &nbsp; -> U+00A0)
    $plain = strip_tags($content);
    $decoded = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Replace non-breaking spaces (U+00A0) and literal &nbsp; with normal space
    $decoded = preg_replace('/\x{00A0}|&nbsp;/u', ' ', $decoded);
    // Collapse whitespace
    $clean = trim(preg_replace('/\s+/u', ' ', $decoded));
    // Typical SEO length ~160 chars
    return Str::limit($clean, 160, '');
}

private function getConnection(string $country): string
{
    return match ($country) {
        'saudi', '2' => 'sa',
        'egypt', '3' => 'eg',
        'palestine', '4' => 'ps',
        'jordan', '1' => 'jo',
        default => throw new NotFoundHttpException(__('Invalid country selected')),
    };
}

public function index(Request $request)
{
    try {
        $country = $request->input('country', '1');
        $connection = $this->getConnection($country);

        $posts = Post::on($connection)
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('content.dashboard.posts.index', [
            'posts' => $posts,
            'country' => $country,
            'countries' => $this->countries,
            'currentCountry' => $country
        ]);
    } catch (NotFoundHttpException $e) {
        abort(404, $e->getMessage());
    } catch (\Exception $e) {
        Log::error('Error in posts index: ' . $e->getMessage());
        return back()->with('error', __('Error loading posts'));
    }
}

public function create(Request $request)
{
    try {
        $country = $request->input('country', '1');
        $connection = $this->getConnection($country);

        $categories = Category::on($connection)
            ->where('is_active', true)
            ->get();

        return view('content.dashboard.posts.create', [
            'categories' => $categories,
            'country' => $country,
            'countries' => $this->countries
        ]);
    } catch (NotFoundHttpException $e) {
        abort(404, $e->getMessage());
    }
}

public function store(Request $request)
{
try {
    Log::info('Starting post creation', $request->all());

    $validated = $request->validate([
        'country' => 'required|string',
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'category_id' => 'required|exists:categories,id',
        'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:40960|dimensions:min_width=100,min_height=100',
        'meta_description' => 'nullable|string|max:255',
        'keywords' => 'nullable|string|max:255',
        'alt' => 'nullable|string|max:255',
        'is_active' => 'sometimes|boolean',
        'is_featured' => 'sometimes|boolean',
        // attachments (non-image, non-code)
        'attachments' => 'sometimes|array',
        'attachments.*' => 'file|mimes:pdf,txt,csv,rtf,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp,zip,rar,7z,tar,gz|max:40960',
    ]);

    $connection = $this->getConnection($validated['country']);
    Log::info('Using connection: ' . $connection);

    // معالجة الصورة
    $imagePath = 'posts/default_post_image.jpg';
    if ($request->hasFile('image')) {
      $imagePath = $this->storeImage($request->file('image'));
    }

    // توليد slug تلقائيًا إذا لم يتم توفيره
    $slug = Str::slug($validated['title']) . '-' . time();

    DB::connection($connection)->beginTransaction();

    try {
        $post = new Post();
        $post->setConnection($connection);
        $post->title = $validated['title'];
        $post->slug = $slug;
        $post->content = $this->normalizeContentHtml($validated['content']);
        $post->category_id = $validated['category_id'];
        $post->image = $imagePath;
        $post->meta_description = $this->makeMetaDescription($validated['meta_description'] ?? null, $validated['content']);
        $post->keywords = $validated['keywords'] ?? implode(',', array_slice(explode(' ', $validated['title']), 0, 2));
        $post->alt = $validated['alt'] ?: $validated['title'];
        $post->is_active = $request->boolean('is_active', true);
        $post->is_featured = $request->boolean('is_featured', false);
        $post->views = 0;
        $post->country = $validated['country'];
        $post->author_id = Auth::id();
        $post->save();

         // 🔹 ربط الكلمات الدلالية بجدول `news_keyword`
         $this->attachKeywords($post, $post->keywords, $connection);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $uploaded) {
                if (!$uploaded->isValid()) { continue; }
                try {
                    $fileInfo = $this->securelyStoreAttachment($uploaded, 'files/posts');
                    File::on($connection)->create([
                        'post_id' => $post->id,
                        'file_path' => $fileInfo['path'],
                        'file_type' => $fileInfo['extension'],
                        'file_category' => 'attachment',
                        'file_name' => $fileInfo['filename'],
                        'file_size' => $fileInfo['size'],
                        'mime_type' => $fileInfo['mime_type'],
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to store post attachment', ['error' => $e->getMessage()]);
                }
            }
        }

        DB::connection($connection)->commit();
        Log::info('Post created successfully', ['post_id' => $post->id]);

        // توليد خريطة الموقع تلقائياً
        GenerateSitemapJob::dispatch($connection, 'posts');

        return redirect()
            ->route('dashboard.posts.index', ['country' => $validated['country']])
            ->with('success', __('Post created successfully'));

    } catch (\Exception $e) {
        DB::connection($connection)->rollBack();
        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($imagePath);
        }
        throw $e;
    }

} catch (\Exception $e) {
    Log::error('Error creating post: ' . $e->getMessage());
    return back()->withInput()->with('error', __('Error creating post: ') . $e->getMessage());
}
}


public function edit($id, Request $request)
{
    try {
        $country = $request->input('country', '1');
        $connection = $this->getConnection($country);

        $post = Post::on($connection)->with('attachments')->findOrFail($id);
        $categories = Category::on($connection)
            ->where('is_active', true)
            ->get();

        return view('content.dashboard.posts.edit', [
            'post' => $post,
            'categories' => $categories,
            'country' => $country,
            'countries' => $this->countries
        ]);
    } catch (NotFoundHttpException $e) {
        abort(404, $e->getMessage());
    } catch (\Exception $e) {
        Log::error('Error editing post: ' . $e->getMessage());
        abort(404, __('Post not found'));
    }
}

public function update(Request $request, $id)
{
    try {
        $validated = $request->validate([
            'country' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'meta_description' => 'nullable|string|max:255',
            'keywords' => 'nullable|string|max:255',
            'alt' => 'nullable|string|max:255',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:40960|dimensions:min_width=100,min_height=100',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'file|mimes:pdf,txt,csv,rtf,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp,zip,rar,7z,tar,gz|max:40960',
        ]);

        $connection = $this->getConnection($validated['country']);
        $post = Post::on($connection)->findOrFail($id);

        DB::connection($connection)->beginTransaction();

        try {
            if ($request->hasFile('image')) {
                if ($post->image && $post->image !== 'posts/default_post_image.jpg') {
                    Storage::disk('public')->delete($post->image);
                }
                if ($post->image && $post->image !== 'posts/default_post_image.jpg') {
                  Storage::disk('public')->delete($post->image);
              }

              $post->image = $this->storeImage($request->file('image'));
            }

            $post->title = $validated['title'];
            $post->slug = Str::slug($validated['title']) . '-' . time();
            $post->content = $this->normalizeContentHtml($validated['content']);
            $post->category_id = $validated['category_id'];
            $post->meta_description = $this->makeMetaDescription($validated['meta_description'] ?? null, $validated['content']);
            $post->keywords = $validated['keywords'] ?? implode(',', array_slice(explode(' ', $validated['title']), 0, 2));
            $post->alt = $validated['alt'] ?: $validated['title'];
            $post->is_active = $request->boolean('is_active', true);
            $post->is_featured = $request->boolean('is_featured', false);
            $post->country = $validated['country'];
            $post->author_id = Auth::id();
            $post->save();


        // 🔹 تحديث الكلمات الدلالية في `news_keyword`
        $this->attachKeywords($post, $post->keywords, $connection);

            // Handle new attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $uploaded) {
                    if (!$uploaded->isValid()) { continue; }
                    try {
                        $fileInfo = $this->securelyStoreAttachment($uploaded, 'files/posts');
                        File::on($connection)->create([
                            'post_id' => $post->id,
                            'file_path' => $fileInfo['path'],
                            'file_type' => $fileInfo['extension'],
                            'file_category' => 'attachment',
                            'file_name' => $fileInfo['filename'],
                            'file_size' => $fileInfo['size'],
                            'mime_type' => $fileInfo['mime_type'],
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to store post attachment (update)', ['error' => $e->getMessage()]);
                    }
                }
            }

            DB::connection($connection)->commit();

            // توليد خريطة الموقع تلقائياً
            GenerateSitemapJob::dispatch($connection, 'posts');

            return redirect()
                ->route('dashboard.posts.index', ['country' => $validated['country']])
                ->with('success', __('Post updated successfully'));

        } catch (\Exception $e) {
            DB::connection($connection)->rollBack();
            throw $e;
        }

    } catch (\Exception $e) {
        Log::error('Error updating post: ' . $e->getMessage());
        return back()->withInput()->with('error', __('Error updating post: ') . $e->getMessage());
    }
}

private function attachKeywords($post, $keywords, $connection)
{
$keywordsArray = array_map('trim', explode(',', $keywords));

foreach ($keywordsArray as $keyword) {
    if (!empty($keyword)) {
        $keywordModel = \App\Models\Keyword::on($connection)->firstOrCreate(['keyword' => $keyword]);
        $post->keywords()->syncWithoutDetaching([$keywordModel->id]);
    }
}
}


public function destroy(Request $request, $id)
{
    try {
        $country = $request->input('country', '1');
        $connection = $this->getConnection($country);

        $post = Post::on($connection)->findOrFail($id);

        DB::connection($connection)->beginTransaction();

        try {
            // حذف الصورة
            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }

            $post->delete();

            DB::connection($connection)->commit();

            // توليد خريطة الموقع تلقائياً
            GenerateSitemapJob::dispatch($connection, 'posts');

            return redirect()
            ->route('dashboard.posts.index', ['country' => $country])
            ->with('success', __('Post deleted successfully'));

        } catch (\Exception $e) {
            DB::connection($connection)->rollBack();
            throw $e;
        }

    } catch (\Exception $e) {
        Log::error('Error deleting post: ' . $e->getMessage());
        return back()->with('error', __('Error deleting post'));
    }
}

/**
 * Toggle the status of the specified post.
 *
 * @param \App\Models\Post $post
 * @return \Illuminate\Http\JsonResponse
 */
public function toggleStatus(Post $post)
{
    try {
        $country = request('country', '1'); // تعيين قيمة افتراضية إذا لم يتم تمرير الدولة
        $connection = $this->getConnection($country);

        DB::connection($connection)->beginTransaction();

        $post->is_active = !$post->is_active;
        $post->save();

        DB::connection($connection)->commit();

        return redirect()->back()->with('success', __('Status updated successfully'));

    } catch (\Exception $e) {
        DB::connection($connection)->rollBack();
        return redirect()->back()->with('error', __('Failed to update status'));
    }
}


/**
 * Toggle the featured status of the specified post.
 *
 * @param \App\Models\Post $post
 * @return \Illuminate\Http\JsonResponse
 */
public function toggleFeatured(Post $post)
{
try {
    $country = request('country', '1'); // تعيين قيمة افتراضية إذا لم يتم تمرير الدولة
    $connection = $this->getConnection($country);

    DB::connection($connection)->beginTransaction();

    $post->is_featured = !$post->is_featured;
    $post->save();

    DB::connection($connection)->commit();

    return redirect()->back()->with('success', __('Featured status updated successfully'));

} catch (\Exception $e) {
    DB::connection($connection)->rollBack();
    return redirect()->back()->with('error', __('Failed to update featured status'));
}
}


private function storeImage($file)
{
    try {
        // استخدام خدمة التحميل الآمن للملفات
        return $this->secureFileUploadService->securelyStoreFile($file, 'images/posts', true);
    } catch (\Exception $e) {
        // تسجيل الخطأ
        Log::error('فشل في تحميل الصورة: ' . $e->getMessage());

        // إرجاع صورة افتراضية في حالة الفشل
        return 'posts/default_post_image.jpg';
    }
}

}
