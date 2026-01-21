<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Resources\BaseResource;

class ImageProxyApiController extends Controller
{
    /**
     * GET /api/img/fit/{size}/{path}
     * Example:
     * /api/img/fit/200x200/storage/articles/cover.jpg
     */
    public function fit(Request $request, string $size, string $path)
    {
        // Validate WxH
        if (!preg_match('/^(\d+)x(\d+)$/', $size, $m)) {
            return (new BaseResource(['error' => 'Invalid size format']))
                ->response($request)
                ->setStatusCode(400);
        }

        $w = (int) $m[1];
        $h = (int) $m[2];

        // Hard limits
        $w = max(1, min($w, 4096));
        $h = max(1, min($h, 4096));

        // Clean path
        $cleanPath = ltrim(urldecode($path), '/');
        $cleanPath = str_replace(['..\\', '../', '\\'], ['', '', '/'], $cleanPath);

        // Try public/ then storage/
        $publicPath = public_path($cleanPath);

        $storageCandidate = $cleanPath;
        if (Str::startsWith($storageCandidate, 'storage/')) {
            $storageCandidate = substr($storageCandidate, strlen('storage/'));
        }
        $storagePath = storage_path('app/public/' . ltrim($storageCandidate, '/'));

        $source = null;
        if (is_file($publicPath)) {
            $source = realpath($publicPath);
        } elseif (is_file($storagePath)) {
            $source = realpath($storagePath);
        }

        if (!$source) {
            Log::warning('ImageProxyApi: source not found', [
                'requested' => $path,
                'public' => $publicPath,
                'storage' => $storagePath
            ]);
            return (new BaseResource(['error' => 'Image not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        // Extension
        $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];

        if (!in_array($ext, $allowed, true)) {
            // Security: Prevent serving non-image files
            return (new BaseResource(['error' => 'File type not allowed']))
                ->response($request)
                ->setStatusCode(403);
        }

        // Cache path
        $algoVersion = 'fit-v2';
        $hash = substr(sha1($source . '|' . filemtime($source) . "|{$w}x{$h}|{$algoVersion}"), 0, 20);
        $cacheRel = "resized/{$w}x{$h}/{$hash}.{$ext}";
        $cacheAbs = storage_path('app/public/' . $cacheRel);

        // Serve from cache
        if (is_file($cacheAbs)) {
            return $this->streamFile($cacheAbs, $ext);
        }

        @mkdir(dirname($cacheAbs), 0775, true);

        // Try Intervention Image v3+
        if (class_exists('Intervention\\Image\\ImageManager')) {
            try {
                if (class_exists('Intervention\\Image\\Drivers\\Gd\\Driver')) {
                    $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                } else {
                    $manager = \Intervention\Image\ImageManager::gd();
                }

                $img = $manager->read($source)->scaleDown($w, $h);

                switch ($ext) {
                    case 'jpg':
                    case 'jpeg':
                        $img->toJpeg(80)->save($cacheAbs); break;
                    case 'png':
                        $img->toPng()->save($cacheAbs); break;
                    case 'webp':
                        $img->toWebp(80)->save($cacheAbs); break;
                    default:
                        $img->save($cacheAbs);
                }

                return $this->streamFile($cacheAbs, $ext);

            } catch (\Throwable $e) {
                // fallback
            }
        }

        // GD Fallback
        try {
            $info = getimagesize($source);
            if (!$info) return $this->streamOriginal($source);

            [$srcW, $srcH, $type] = $info;

            switch ($type) {
                case IMAGETYPE_JPEG:
                    $src = imagecreatefromjpeg($source); break;
                case IMAGETYPE_PNG:
                    $src = imagecreatefrompng($source); break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) {
                        $src = imagecreatefromwebp($source); 
                        break;
                    }
                    return $this->streamOriginal($source);
                default:
                    return $this->streamOriginal($source);
            }

            $scale = min($w / max($srcW,1), $h / max($srcH,1));
            $newW = max(1, (int) floor($srcW * $scale));
            $newH = max(1, (int) floor($srcH * $scale));

            $dst = imagecreatetruecolor($newW, $newH);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $transparent);

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

            switch ($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($dst, $cacheAbs, 80); break;
                case IMAGETYPE_PNG:
                    imagepng($dst, $cacheAbs, 7); break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagewebp')) imagewebp($dst, $cacheAbs, 80);
                    break;
            }

            // In PHP 8.0+, GD resources are objects and automatically garbage collected.
            // imagedestroy() is deprecated and no longer needed.
            unset($src, $dst);

            return $this->streamFile($cacheAbs, $ext);

        } catch (\Throwable $e) {
            return $this->streamOriginal($source);
        }
    }

    protected function streamOriginal(string $path)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return $this->streamFile($path, $ext);
    }

    protected function streamFile(string $path, string $ext)
    {
        $mime = match ($ext) {
            'jpg','jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => mime_content_type($path) ?: 'application/octet-stream'
        };

        return Response::file($path, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=2592000',
            'Expires' => gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT'
        ]);
    }
}
