<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FileSecurityService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\BaseResource;
use App\Http\Requests\SecureFile\SecureFileViewRequest;

class SecureFileApiController extends Controller
{
    protected $fileSecurity;

    public function __construct(FileSecurityService $fileSecurity)
    {
        $this->fileSecurity = $fileSecurity;
        $this->middleware('auth:sanctum')->except(['view']);
    }

    /**
     * Upload secure image
     */
    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|max:10240',
        ]);

        if ($validator->fails()) {
            return (new BaseResource(['error' => $validator->errors()->first()]))
                ->response($request)
                ->setStatusCode(422);
        }

        try {
            $file = $request->file('file');

            [$safe, $msg] = $this->fileSecurity->scanFile($file, 'image');
            if (!$safe) {
                return (new BaseResource(['error' => $msg]))
                    ->response($request)
                    ->setStatusCode(400);
            }

            $data = $this->fileSecurity->securelyStoreFile($file, 'image');

            Log::info('Secure image uploaded', [
                'user_id' => $request->user()->id,
                'filename' => $data['filename']
            ]);

            return new BaseResource([
                'message' => 'Image uploaded successfully.',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Image upload exception', ['error' => $e->getMessage()]);
            return (new BaseResource(['error' => 'Server error.']))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * Upload secure document file
     */
    public function uploadDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt',
        ]);

        if ($validator->fails()) {
            return (new BaseResource(['error' => $validator->errors()->first()]))
                ->response($request)
                ->setStatusCode(422);
        }

        try {
            $file = $request->file('file');

            [$safe, $msg] = $this->fileSecurity->scanFile($file, 'document');
            if (!$safe) {
                return (new BaseResource(['error' => $msg]))
                    ->response($request)
                    ->setStatusCode(400);
            }

            $data = $this->fileSecurity->securelyStoreFile($file, 'document');

            Log::info('Secure document uploaded', [
                'user_id' => $request->user()->id,
                'filename' => $data['filename']
            ]);

            return new BaseResource([
                'message' => 'Document uploaded successfully.',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Document upload exception', ['error' => $e->getMessage()]);
            return (new BaseResource(['error' => 'Server error.']))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * Secure file view (Token-protected)
     */
    public function view(SecureFileViewRequest $request)
    {
        $path = $request->path;
        $token = $request->token;

        $expected = hash_hmac('sha256', $path, config('app.key'));

        if (!hash_equals($expected, $token)) {
            Log::warning('Unauthorized secure file access', [
                'path' => $path,
                'ip' => $request->ip()
            ]);

            return (new BaseResource(['error' => 'Forbidden']))
                ->response($request)
                ->setStatusCode(403);
        }

        if (!Storage::disk('private')->exists($path)) {
            return (new BaseResource(['error' => 'File not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        $fullPath = Storage::disk('private')->path($path);
        $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
        $content = Storage::disk('private')->get($path);

        return Response::make($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'no-cache'
        ]);
    }
}
