<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - API ONLY MODE
|--------------------------------------------------------------------------
|
| هذا المشروع يعمل كـ API فقط.
| الواجهة الأمامية (Frontend) على Next.js في https://alemancenter.com
| لا توجد routes web هنا - فقط API routes في routes/api.php
|
*/

// ==============================================
// Legacy SEO Redirects (للروابط القديمة فقط)
// ==============================================

// Redirect old vBulletin URLs to frontend
$frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

Route::get('/vb/node/{id}', function ($id) use ($frontendUrl) {
    return redirect($frontendUrl . '/jo/posts/' . $id, 301);
});

Route::get('/vb/search', function () use ($frontendUrl) {
    return redirect($frontendUrl . '/search', 301);
});

Route::get('/vb/{any}', function () use ($frontendUrl) {
    return redirect($frontendUrl, 301);
})->where('any', '.*');

Route::get('/forum/{any?}', function () use ($frontendUrl) {
    return redirect($frontendUrl, 301);
})->where('any', '.*');

Route::get('/threads/{any?}', function () use ($frontendUrl) {
    return redirect($frontendUrl, 301);
})->where('any', '.*');

Route::get('/up/{any?}', function () use ($frontendUrl) {
    return redirect($frontendUrl, 301);
})->where('any', '.*');

Route::get('/up/do.php', function () use ($frontendUrl) {
    return redirect($frontendUrl, 301);
});

// ==============================================
// API Information Endpoint
// ==============================================

// الصفحة الرئيسية ترجع معلومات API
Route::get('/', function () use ($frontendUrl) {
    return response()->json([
        'message' => 'API Server',
        'status' => 'operational',
        'version' => '1.0',
        'documentation' => 'For API documentation, please contact the administrator',
        'website' => $frontendUrl,
    ], 200)->header('Content-Type', 'application/json');
});

// ==============================================
// Catch-all: Redirect everything else to frontend
// ==============================================

// أي مسار آخر غير /api/* يتم توجيهه للفرونت اند
Route::fallback(function () use ($frontendUrl) {
    return response()->json([
        'error' => 'Not Found',
        'message' => 'The requested resource was not found',
        'website' => $frontendUrl,
    ], 404);
});
