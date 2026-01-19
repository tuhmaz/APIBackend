<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImageProxyController extends Controller
{
    // GET /img/fit/{size}/{path}
    // {size} like 220x220
    // {path} remaining path to public/ or storage/app/public asset
    public function fit(Request $request, string $size, string $path)
    {
        // Validate size
        if (!preg_match('/^(\d+)x(\d+)$/', $size, $m)) {
            return Response::make('Invalid size', 400);
        }
        $w = (int) $m[1];
        $h = (int) $m[2];
        $w = max(1, min($w, 4096));
        $h = max(1, min($h, 4096));

        // Normalize and decode path
        $cleanPath = ltrim(urldecode($path), '/');
        // Prevent directory traversal
        $cleanPath = str_replace(['..\\', '../', '\\'], ['','', '/'], $cleanPath);

        // Try to resolve source file under public/ or storage/app/public
        $publicPath = public_path($cleanPath);
        // Support both with and without public/storage symlink
        $storageCandidate = $cleanPath;
        if (Str::startsWith($storageCandidate, 'storage/')) {
            // Map 'storage/xyz' to 'app/public/xyz'
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
              Log::warning('ImageProxy not found', [
                'requested' => $path,
                'clean' => $cleanPath,
                'publicPath' => $publicPath,
                'storagePath' => $storagePath,
            ]);
            return Response::make('Not found', 404);
        }

        // Build cache file path
        $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed, true)) {
            // For non-image types, just stream original
            return $this->streamOriginal($source);
        }

        // Include an algorithm version to invalidate previous cached variants if logic changes
        $algoVersion = 'fit-v2';
        $hash = substr(sha1($source . '|' . filemtime($source) . "|{$w}x{$h}|{$algoVersion}"), 0, 20);
        $cacheRel = "resized/{$w}x{$h}/{$hash}.{$ext}";
        $cacheAbs = storage_path('app/public/' . $cacheRel);

        // If cached, serve cached
        if (is_file($cacheAbs)) {
            return $this->streamFile($cacheAbs, $ext);
        }

        // Ensure directory exists
        @mkdir(dirname($cacheAbs), 0775, true);

        // Try Intervention Image first if available
        if (class_exists('Intervention\\Image\\ImageManager')) {
            try {
                // Prefer v3 style manager with GD driver if available
                if (class_exists('Intervention\\Image\\Drivers\\Gd\\Driver')) {
                    $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                } else {
                    // Fallback to older static helper if present
                    $manager = \Intervention\Image\ImageManager::gd();
                }
                $img = $manager->read($source);
                // Preserve aspect ratio and fit WITHIN WxH, no cropping
                $img = $img->scaleDown($w, $h);

                // Encode with reasonable quality
                switch ($ext) {
                    case 'jpg':
                    case 'jpeg':
                        $img->toJpeg(80)->save($cacheAbs);
                        break;
                    case 'png':
                        $img->toPng()->save($cacheAbs);
                        break;
                    case 'webp':
                        $img->toWebp(80)->save($cacheAbs);
                        break;
                    default:
                        $img->save($cacheAbs);
                }

                return $this->streamFile($cacheAbs, $ext);
            } catch (\Throwable $e) {
                // Fallback to GD
            }
        }

        // GD fallback (contain/fit within box, keeps original format)
        try {
            $imageInfo = getimagesize($source);
            if (!$imageInfo) return $this->streamOriginal($source);
            [$srcW, $srcH, $type] = $imageInfo;
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $src = imagecreatefromjpeg($source); break;
                case IMAGETYPE_PNG:
                    $src = imagecreatefrompng($source); break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) {
                        $src = imagecreatefromwebp($source); break;
                    }
                    return $this->streamOriginal($source);
                default:
                    return $this->streamOriginal($source);
            }
            // Compute contain (fit within WxH)
            $scale = min($w / max($srcW, 1), $h / max($srcH, 1));
            $newW = max(1, (int) floor($srcW * $scale));
            $newH = max(1, (int) floor($srcH * $scale));

            $dst = imagecreatetruecolor($newW, $newH);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            // Preserve transparency for PNG/WebP
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $transparent);

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

            switch ($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($dst, $cacheAbs, 80); break;
                case IMAGETYPE_PNG:
                    imagepng($dst, $cacheAbs, 7); break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagewebp')) imagewebp($dst, $cacheAbs, 80); else return $this->streamOriginal($source);
                    break;
            }
            imagedestroy($src); imagedestroy($dst);

            return $this->streamFile($cacheAbs, $ext);
        } catch (\Throwable $e) {
            // Fallback to original
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
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => mime_content_type($path) ?: 'application/octet-stream',
        };
        $seconds = 60 * 60 * 24 * 30; // 30 days
        return Response::file($path, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=' . $seconds,
            'Expires' => gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT',
        ]);
    }
}
