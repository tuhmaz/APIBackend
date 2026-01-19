<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Log;
use App\Services\ImageOptimizationService;
use App\Http\Resources\BaseResource;

class ImageUploadApiController extends Controller
{
    protected $imageOptimizer;

    public function __construct(ImageOptimizationService $imageOptimizer)
    {
        $this->imageOptimizer = $imageOptimizer;
    }

    /**
     * API: Upload and optimize image
     */
    public function upload(Request $request)
    {
        try {
            if (!$request->hasFile('file')) {
                return (new BaseResource(['error' => 'No file uploaded.']))
                    ->response($request)
                    ->setStatusCode(400);
            }

            $file = $request->file('file');

            if (!$file->isValid() ||
                !in_array(strtolower($file->extension()), [
                    'jpg','jpeg','png','gif','bmp','tiff','webp'
                ])) {
                return (new BaseResource(['error' => 'Invalid image file.']))
                    ->response($request)
                    ->setStatusCode(400);
            }

            $filename = Str::random(12) . '.' . $file->getClientOriginalExtension();
            $temp = 'temp/' . $filename;

            Storage::disk('public')->put($temp, file_get_contents($file->getRealPath()));

            $options = [
                'max_width' => $request->input('width', 1920),
                'quality'   => $request->input('quality', 85),
                'convert_to_webp' => $request->boolean('convert_to_webp', true),
            ];

            /**
             * Responsive Versions
             */
            if ($request->boolean('create_responsive')) {
                $options['sizes'] = [
                    ['width' => 1280, 'height' => null, 'suffix' => 'lg'],
                    ['width' => 768,  'height' => null, 'suffix' => 'md'],
                    ['width' => 480,  'height' => null, 'suffix' => 'sm'],
                ];

                $resultPaths = $this->imageOptimizer->optimizeAndCreateResponsive($temp, 'public', $options);

                $urls = [];
                foreach ($resultPaths as $path) {
                    $new = str_replace('temp/', 'images/', $path);
                    Storage::disk('public')->move($path, $new);
                    $urls[] = Storage::url($new);
                }

                $main = str_replace('temp/', 'images/', $resultPaths[0]);

                $img = Image::read(Storage::disk('public')->get($main));

                return (new BaseResource([
                    'url' => Storage::url($main),
                    'width' => $img->width(),
                    'height' => $img->height(),
                    'responsive_urls' => $urls,
                    'optimized' => true,
                ]))->response($request)->setStatusCode(200);

            } else {
                /**
                 * Normal Optimization
                 */
                $optimized = $this->imageOptimizer->optimize($temp, 'public', $options);

                $final = str_replace('temp/', 'images/', $optimized);

                Storage::disk('public')->move($optimized, $final);

                $img = Image::read(Storage::disk('public')->get($final));

                return (new BaseResource([
                    'url' => Storage::url($final),
                    'width' => $img->width(),
                    'height' => $img->height(),
                    'optimized' => true,
                ]))->response($request)->setStatusCode(200);
            }

        } catch (\Exception $e) {
            Log::error('Image upload error', ['error' => $e->getMessage()]);

            return (new BaseResource([
                'error' => 'Image processing error',
                'message' => $e->getMessage(),
            ]))->response($request)->setStatusCode(500);
        }
    }

    /**
     * API: Upload general files (PDF, DOCX, ZIP…)
     */
    public function uploadFile(Request $request)
    {
        if (!$request->hasFile('file')) {
            return (new BaseResource(['error' => 'No file uploaded.']))
                ->response($request)
                ->setStatusCode(400);
        }

        $file = $request->file('file');

        $original = $file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension();
        $base = pathinfo($original, PATHINFO_FILENAME);

        // Keep Arabic + letters + numbers
        $base = preg_replace('/[^\x{0600}-\x{06FF}a-zA-Z0-9 _()\-]+/u', '', $base);
        $base = trim(preg_replace('/\s+/u', ' ', $base));

        if ($base === '') {
            $base = 'file';
        }

        $filename = $base . '.' . $ext;

        $counter = 2;
        while (Storage::disk('public')->exists('files/' . $filename)) {
            $filename = $base . '-' . $counter . '.' . $ext;
            $counter++;
        }

        Storage::disk('public')->putFileAs('files', $file, $filename);

        return (new BaseResource([
            'url' => Storage::url('files/' . $filename),
            'name' => $filename
        ]))->response($request)->setStatusCode(200);
    }
}
