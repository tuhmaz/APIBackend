<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\FrontendApiGuard;

use App\Http\Controllers\Api\ActivityApiController;
use App\Http\Controllers\Api\AnalyticsApiController;
use App\Http\Controllers\Api\ArticleApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\BlockedIpsApiController;
use App\Http\Controllers\Api\CalendarApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\CommentApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\FileApiController;
use App\Http\Controllers\Api\FilterApiController;
use App\Http\Controllers\Api\FrontApiController;
use App\Http\Controllers\Api\GradeOneApiController;
use App\Http\Controllers\Api\HomeApiController;
use App\Http\Controllers\Api\ImageProxyApiController;
use App\Http\Controllers\Api\ImageUploadApiController;
use App\Http\Controllers\Api\KeywordApiController;
use App\Http\Controllers\Api\LegalApiController;
use App\Http\Controllers\Api\LocalizationApiController;
use App\Http\Controllers\Api\MessageApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\PerformanceApiController;
use App\Http\Controllers\Api\PermissionApiController;
use App\Http\Controllers\Api\PostApiController;
use App\Http\Controllers\Api\ReactionApiController;
use App\Http\Controllers\Api\RedisApiController;
use App\Http\Controllers\Api\RoleApiController;
use App\Http\Controllers\Api\SchoolClassApiController;
use App\Http\Controllers\Api\SecureFileApiController;
use App\Http\Controllers\Api\SecurityLogApiController;
use App\Http\Controllers\Api\SecurityMonitorApiController;
use App\Http\Controllers\Api\SemesterApiController;
use App\Http\Controllers\Api\SubjectApiController;
use App\Http\Controllers\Api\SitemapApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\TrustedIpApiController;
use App\Http\Controllers\Api\SettingsApiController;

// ========================================================================
// HEALTH CHECK - No protection needed
// ========================================================================
Route::get('/ping', function () {
    return response()->json(['status' => 'ok', 'time' => now()->toISOString()]);
})->withoutMiddleware(['throttle:api']);

// ========================================================================
// UNPROTECTED ROUTES - OAuth callbacks and email verification
// These MUST be accessible without frontend protection
// ========================================================================
Route::prefix('auth')->group(function () {
    // Google OAuth callback (redirected from Google)
    Route::get('/google/callback', [AuthApiController::class, 'googleCallback']);

    // Email verification (clicked from email)
    Route::get('/email/verify/{id}/{hash}', [AuthApiController::class, 'verifyEmail'])
        ->name('verification.verify');
});

// Image Proxy - needs to be accessible for image loading
Route::get('/img/fit/{size}/{path}', [ImageProxyApiController::class, 'fit'])
    ->where('path', '.*');

// Secure file viewing with token
Route::get('/secure/view', [SecureFileApiController::class, 'view']);

// ========================================================================
// ALL PROTECTED API ROUTES - Frontend Guard Applied
// ========================================================================
Route::middleware([FrontendApiGuard::class])->group(function () {

    // ==== Language ====
    Route::prefix('lang')->group(function () {
        Route::post('change', [LocalizationApiController::class, 'changeLanguage']);
        Route::get('current', [LocalizationApiController::class, 'currentLanguage']);
    });

    // ==== Auth Routes ====
    Route::prefix('auth')->group(function () {
        // Registration & Login
        Route::post('/register', [AuthApiController::class, 'register']);
        Route::post('/login', [AuthApiController::class, 'login']);

        // Password Reset
        Route::post('/password/forgot', [AuthApiController::class, 'forgotPassword']);
        Route::post('/password/reset', [AuthApiController::class, 'resetPassword']);

        // Google OAuth redirect
        Route::get('/google/redirect', [AuthApiController::class, 'googleRedirect']);

        // Authenticated routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/user', [AuthApiController::class, 'me']);
            Route::put('/profile', [AuthApiController::class, 'updateProfile']);
            Route::post('/logout', [AuthApiController::class, 'logout']);
            Route::post('/email/resend', [AuthApiController::class, 'resendVerifyEmail']);
        });
    });

    // ==== Public Content Routes (Protected by Frontend Guard) ====

    // School Classes
    Route::get('/school-classes', [SchoolClassApiController::class, 'index']);
    Route::get('/school-classes/{id}', [SchoolClassApiController::class, 'show']);

    // Filters
    Route::prefix('filter')->group(function () {
        Route::get('/', [FilterApiController::class, 'index']);
        Route::get('/subjects/{classId}', [FilterApiController::class, 'getSubjectsByClass']);
        Route::get('/semesters/{subjectId}', [FilterApiController::class, 'getSemestersBySubject']);
        Route::get('/file-types/{semesterId}', [FilterApiController::class, 'getFileTypesBySemester']);
    });

    // Articles
    Route::get('/articles', [ArticleApiController::class, 'index']);
    Route::get('/articles/{id}', [ArticleApiController::class, 'show'])->whereNumber('id');
    Route::get('/articles/file/{id}/download', [ArticleApiController::class, 'download']);
    Route::get('/articles/by-class/{grade_level}', [ArticleApiController::class, 'indexByClass']);
    Route::get('/articles/by-keyword/{keyword}', [ArticleApiController::class, 'indexByKeyword']);

    // Categories
    Route::get('/categories', [CategoryApiController::class, 'index']);
    Route::get('/categories/{id}', [CategoryApiController::class, 'show']);

    // Posts
    Route::get('/posts', [PostApiController::class, 'index']);
    Route::get('/posts/{id}', [PostApiController::class, 'show']);
    Route::post('/posts/{id}/increment-view', [PostApiController::class, 'incrementView']);

    // Comments
    Route::get('/comments/{database}', [CommentApiController::class, 'index']);

    // Keywords
    Route::get('/keywords', [KeywordApiController::class, 'index']);
    Route::get('/keywords/{keyword}', [KeywordApiController::class, 'show']);

    // Grades
    Route::prefix('grades')->group(function () {
        Route::get('/', [GradeOneApiController::class, 'index']);
        Route::get('/{id}', [GradeOneApiController::class, 'show']);
        Route::get('/subjects/{id}', [GradeOneApiController::class, 'showSubject']);
        Route::get('/subjects/{subject}/semesters/{semester}/category/{category}',
            [GradeOneApiController::class, 'subjectArticles']);
        Route::get('/articles/{id}', [GradeOneApiController::class, 'showArticle']);
        Route::get('/files/{id}/download', [GradeOneApiController::class, 'downloadFile']);
    });

    // Home
    Route::prefix('home')->group(function () {
        Route::get('/', [HomeApiController::class, 'index']);
        Route::get('/calendar', [HomeApiController::class, 'getCalendarEvents']);
        Route::get('/event/{id}', [HomeApiController::class, 'getEventDetails']);
    });

    // Front (Settings, Contact, Members)
    Route::prefix('front')->group(function () {
        Route::get('/settings', [FrontApiController::class, 'settings']);
        Route::post('/contact', [FrontApiController::class, 'submitContact']);
        Route::get('/members', [FrontApiController::class, 'members']);
        Route::get('/members/{id}', [FrontApiController::class, 'showMember']);
        Route::post('/members/{id}/contact', [FrontApiController::class, 'contactMember']);
    });

    // Legal Pages
    Route::prefix('legal')->group(function () {
        Route::get('privacy-policy', [LegalApiController::class, 'privacyPolicy']);
        Route::get('terms-of-service', [LegalApiController::class, 'termsOfService']);
        Route::get('cookie-policy', [LegalApiController::class, 'cookiePolicy']);
        Route::get('disclaimer', [LegalApiController::class, 'disclaimer']);
    });

    // ==== Authenticated User Routes ====
    Route::middleware('auth:sanctum')->group(function () {

        // Reactions
        Route::post('/reactions', [ReactionApiController::class, 'store']);
        Route::delete('/reactions/{comment_id}', [ReactionApiController::class, 'destroy']);
        Route::get('/reactions/{comment_id}', [ReactionApiController::class, 'show']);

        // Roles (top-level)
        Route::get('/roles', [RoleApiController::class, 'index']);
        Route::get('/roles/{id}', [RoleApiController::class, 'show']);
        Route::post('/roles', [RoleApiController::class, 'store']);
        Route::put('/roles/{id}', [RoleApiController::class, 'update']);
        Route::delete('/roles/{id}', [RoleApiController::class, 'destroy']);

        // File uploads
        Route::post('/upload/image', [ImageUploadApiController::class, 'upload']);
        Route::post('/upload/file', [ImageUploadApiController::class, 'uploadFile']);
    });

    // ==== Dashboard Routes (Authenticated) ====
    Route::middleware('auth:sanctum')->prefix('dashboard')->group(function () {

        // Dashboard Home
        Route::get('/', [DashboardApiController::class, 'index']);
        Route::get('/content-analytics', [DashboardApiController::class, 'analytics']);

        // Activities
        Route::get('/activities', [ActivityApiController::class, 'index']);
        Route::get('/activities/load-more', [ActivityApiController::class, 'loadMore']);
        Route::delete('/activities/clean', [ActivityApiController::class, 'cleanOldActivities']);

        // Analytics (requires permission)
        Route::middleware(['can:manage monitoring'])->group(function () {
            Route::get('/visitor-analytics', [AnalyticsApiController::class, 'index']);
        });

        // Articles Management (requires permission)
        Route::middleware(['can:manage articles'])->group(function () {
            Route::get('/articles/stats', [ArticleApiController::class, 'stats']);
            Route::get('/articles', [ArticleApiController::class, 'index']);
            Route::get('/articles/{id}', [ArticleApiController::class, 'show'])->whereNumber('id');
            Route::get('/articles/create', [ArticleApiController::class, 'create']);
            Route::post('/articles', [ArticleApiController::class, 'store']);
            Route::get('/articles/{id}/edit', [ArticleApiController::class, 'edit'])->whereNumber('id');
            Route::put('/articles/{id}', [ArticleApiController::class, 'update'])->whereNumber('id');
            Route::delete('/articles/{id}', [ArticleApiController::class, 'destroy'])->whereNumber('id');
            Route::post('/articles/{id}/publish', [ArticleApiController::class, 'publish'])->whereNumber('id');
            Route::post('/articles/{id}/unpublish', [ArticleApiController::class, 'unpublish'])->whereNumber('id');
        });

        // School Classes Management
        Route::get('/school-classes', [SchoolClassApiController::class, 'index']);
        Route::get('/school-classes/{id}', [SchoolClassApiController::class, 'show']);
        Route::post('/school-classes', [SchoolClassApiController::class, 'store']);
        Route::put('/school-classes/{id}', [SchoolClassApiController::class, 'update']);
        Route::delete('/school-classes/{id}', [SchoolClassApiController::class, 'destroy']);

        // Semesters
        Route::prefix('semesters')->group(function () {
            Route::get('/', [SemesterApiController::class, 'index']);
            Route::post('/', [SemesterApiController::class, 'store']);
            Route::get('/{id}', [SemesterApiController::class, 'show']);
            Route::put('/{id}', [SemesterApiController::class, 'update']);
            Route::delete('/{id}', [SemesterApiController::class, 'destroy']);
        });

        // Subjects
        Route::prefix('subjects')->group(function () {
            Route::get('/', [SubjectApiController::class, 'index']);
            Route::post('/', [SubjectApiController::class, 'store']);
            Route::get('/{id}', [SubjectApiController::class, 'show']);
            Route::put('/{id}', [SubjectApiController::class, 'update']);
            Route::delete('/{id}', [SubjectApiController::class, 'destroy']);
        });

        // Sitemap
        Route::prefix('sitemap')->group(function () {
            Route::get('/status', [SitemapApiController::class, 'status']);
            Route::post('/generate', [SitemapApiController::class, 'generateAll']);
            Route::delete('/delete/{type}/{database}', [SitemapApiController::class, 'delete']);
        });

        // Roles & Permissions (requires permission)
        Route::prefix('roles')->middleware('can:manage roles')->group(function () {
            Route::get('/', [RoleApiController::class, 'index']);
            Route::post('/', [RoleApiController::class, 'store']);
            Route::get('/{id}', [RoleApiController::class, 'show']);
            Route::put('/{id}', [RoleApiController::class, 'update']);
            Route::delete('/{id}', [RoleApiController::class, 'destroy']);
        });
        Route::get('/permissions', [RoleApiController::class, 'permissions']);
        Route::post('/permissions', [PermissionApiController::class, 'store'])->middleware('can:manage roles');
        Route::put('/permissions/{id}', [PermissionApiController::class, 'update'])->middleware('can:manage roles');
        Route::delete('/permissions/{id}', [PermissionApiController::class, 'destroy'])->middleware('can:manage roles');

        // User Search
        Route::get('/users/search', [UserApiController::class, 'search']);

        // Users Management (requires permission)
        Route::prefix('users')->middleware('can:manage users')->group(function () {
            Route::get('/', [UserApiController::class, 'index']);
            Route::post('/', [UserApiController::class, 'store']);
            Route::get('/{user}', [UserApiController::class, 'show']);
            Route::put('/{user}', [UserApiController::class, 'update']);
            Route::put('/{user}/roles-permissions', [UserApiController::class, 'updateRolesPermissions']);
            Route::delete('/{user}', [UserApiController::class, 'destroy']);
            Route::post('/bulk-delete', [UserApiController::class, 'bulkDelete']);
            Route::post('/update-status', [UserApiController::class, 'bulkUpdateStatus']);
        });

        // Settings (requires permission)
        Route::middleware(['can:manage settings'])->prefix('settings')->group(function () {
            Route::get('/', [SettingsApiController::class, 'getAll']);
            Route::post('/', [SettingsApiController::class, 'update']);
            Route::post('/update', [SettingsApiController::class, 'update']);
            Route::post('/smtp/test', [SettingsApiController::class, 'testSmtp']);
            Route::post('/smtp/send-test', [SettingsApiController::class, 'sendTestEmail']);
            Route::post('/robots', [SettingsApiController::class, 'updateRobots']);
        });

        // Security Management
        Route::prefix('security')->group(function () {
            Route::get('/stats', [SecurityLogApiController::class, 'quickStats']);
            Route::get('/logs', [SecurityLogApiController::class, 'logs']);
            Route::post('/logs/{id}/resolve', [SecurityLogApiController::class, 'resolve'])->whereNumber('id');
            Route::delete('/logs/{id}', [SecurityLogApiController::class, 'destroy'])->whereNumber('id');
            Route::delete('/logs', [SecurityLogApiController::class, 'destroyAll']);
            Route::get('/analytics', [SecurityLogApiController::class, 'analytics']);
            Route::get('/analytics/routes', [SecurityLogApiController::class, 'topRoutes']);
            Route::get('/analytics/geo', [SecurityLogApiController::class, 'geo']);
            Route::get('/analytics/resolution', [SecurityLogApiController::class, 'resolution']);
            Route::get('/ip/{ip}', [SecurityLogApiController::class, 'ipDetails'])->where('ip', '.*');
            Route::post('/ip/block', [SecurityLogApiController::class, 'blockIp']);
            Route::post('/ip/unblock', [SecurityLogApiController::class, 'unblockIp']);
            Route::post('/ip/trust', [SecurityLogApiController::class, 'trustIp']);
            Route::post('/ip/untrust', [SecurityLogApiController::class, 'untrustIp']);
            Route::get('/blocked-ips', [SecurityLogApiController::class, 'blockedIps']);
            Route::get('/trusted-ips', [SecurityLogApiController::class, 'trustedIps']);
            Route::get('/overview', [SecurityLogApiController::class, 'overview']);
        });

        // Security Monitor (requires permission)
        Route::prefix('security/monitor')->middleware('can:manage security')->group(function () {
            Route::get('/dashboard', [SecurityMonitorApiController::class, 'dashboard']);
            Route::get('/alerts', [SecurityMonitorApiController::class, 'alerts']);
            Route::get('/alerts/{id}', [SecurityMonitorApiController::class, 'showAlert']);
            Route::patch('/alerts/{id}', [SecurityMonitorApiController::class, 'updateAlert']);
            Route::post('/run-scan', [SecurityMonitorApiController::class, 'runScan']);
            Route::post('/export-report', [SecurityMonitorApiController::class, 'exportReport']);
        });

        // Blocked IPs (requires permission)
        Route::prefix('blocked-ips')->middleware('can:manage security')->group(function () {
            Route::get('/', [BlockedIpsApiController::class, 'index']);
            Route::post('/', [BlockedIpsApiController::class, 'store']);
            Route::delete('/{id}', [BlockedIpsApiController::class, 'destroy']);
            Route::delete('/bulk', [BlockedIpsApiController::class, 'bulkDestroy']);
        });

        // Calendar
        Route::prefix('calendar')->group(function () {
            Route::get('/databases', [CalendarApiController::class, 'databases']);
            Route::get('/events', [CalendarApiController::class, 'getEvents']);
            Route::post('/events', [CalendarApiController::class, 'store']);
            Route::put('/events/{id}', [CalendarApiController::class, 'update']);
            Route::delete('/events/{id}', [CalendarApiController::class, 'destroy']);
        });

        // Messages
        Route::prefix('messages')->group(function () {
            Route::get('/inbox', [MessageApiController::class, 'inbox']);
            Route::get('/sent', [MessageApiController::class, 'sent']);
            Route::get('/drafts', [MessageApiController::class, 'drafts']);
            Route::post('/send', [MessageApiController::class, 'send']);
            Route::post('/draft', [MessageApiController::class, 'saveDraft']);
            Route::get('/{id}', [MessageApiController::class, 'show']);
            Route::post('/{id}/read', [MessageApiController::class, 'markAsRead']);
            Route::post('/{id}/important', [MessageApiController::class, 'toggleImportant']);
            Route::delete('/{id}', [MessageApiController::class, 'destroy']);
        });

        // Secure Files
        Route::post('/secure/upload-image', [SecureFileApiController::class, 'uploadImage']);
        Route::post('/secure/upload-document', [SecureFileApiController::class, 'uploadDocument']);

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationApiController::class, 'index']);
            Route::get('/latest', [NotificationApiController::class, 'latest']);
            Route::post('/{id}/read', [NotificationApiController::class, 'markAsRead']);
            Route::post('/read-all', [NotificationApiController::class, 'markAllAsRead']);
            Route::post('/bulk', [NotificationApiController::class, 'bulkAction']);
            Route::delete('/{id}', [NotificationApiController::class, 'destroy']);
        });

        // Redis
        Route::prefix('redis')->group(function () {
            Route::get('/keys', [RedisApiController::class, 'index']);
            Route::post('/', [RedisApiController::class, 'store']);
            Route::delete('/{key}', [RedisApiController::class, 'destroy']);
            Route::delete('/expired/clean', [RedisApiController::class, 'cleanExpired']);
            Route::get('/test', [RedisApiController::class, 'testConnection']);
            Route::get('/info', [RedisApiController::class, 'info']);
            Route::get('/env', [RedisApiController::class, 'envSettings']);
            Route::post('/env', [RedisApiController::class, 'updateEnvSettings']);
        });

        // Performance
        Route::prefix('performance')->group(function () {
            Route::get('/summary', [PerformanceApiController::class, 'summary']);
            Route::get('/live', [PerformanceApiController::class, 'live']);
            Route::get('/raw', [PerformanceApiController::class, 'raw']);
            Route::get('/response-time', [PerformanceApiController::class, 'responseTime']);
            Route::get('/cache', [PerformanceApiController::class, 'cacheStats']);
        });

        // Categories Management
        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryApiController::class, 'index']);
            Route::post('/', [CategoryApiController::class, 'store']);
            Route::get('/{id}', [CategoryApiController::class, 'show']);
            Route::post('/{id}/update', [CategoryApiController::class, 'update']);
            Route::delete('/{id}', [CategoryApiController::class, 'destroy']);
            Route::post('/{id}/toggle', [CategoryApiController::class, 'toggleStatus']);
        });

        // Comments Management
        Route::prefix('comments')->group(function () {
            Route::get('/{database}', [CommentApiController::class, 'index']);
            Route::post('/{database}', [CommentApiController::class, 'store']);
            Route::delete('/{database}/{id}', [CommentApiController::class, 'destroy']);
        });

        // Files Management
        Route::prefix('files')->group(function () {
            Route::get('/', [FileApiController::class, 'index']);
            Route::post('/', [FileApiController::class, 'store']);
            Route::get('/{id}', [FileApiController::class, 'show']);
            Route::get('/{id}/download', [FileApiController::class, 'download']);
            Route::put('/{id}', [FileApiController::class, 'update']);
            Route::delete('/{id}', [FileApiController::class, 'destroy']);
        });

        // Trusted IPs
        Route::prefix('trusted-ips')->group(function () {
            Route::get('/', [TrustedIpApiController::class, 'index']);
            Route::post('/', [TrustedIpApiController::class, 'store']);
            Route::post('/check', [TrustedIpApiController::class, 'check']);
            Route::delete('/{trustedIp}', [TrustedIpApiController::class, 'destroy']);
        });

        // Posts Management
        Route::prefix('posts')->group(function () {
            Route::post('/', [PostApiController::class, 'store']);
            Route::post('/{id}', [PostApiController::class, 'update']);
            Route::post('/{id}/toggle-status', [PostApiController::class, 'toggleStatus']);
            Route::delete('/{id}', [PostApiController::class, 'destroy']);
        });

        // Filters (Dashboard)
        Route::prefix('filter')->group(function () {
            Route::get('/', [FilterApiController::class, 'index']);
            Route::get('/subjects/{classId}', [FilterApiController::class, 'getSubjectsByClass']);
            Route::get('/semesters/{subjectId}', [FilterApiController::class, 'getSemestersBySubject']);
            Route::get('/file-types/{semesterId}', [FilterApiController::class, 'getFileTypesBySemester']);
        });
    });

    // Permissions (top-level, protected)
    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionApiController::class, 'index']);
        Route::post('/', [PermissionApiController::class, 'store']);
        Route::get('/{permission}', [PermissionApiController::class, 'show']);
        Route::put('/{permission}', [PermissionApiController::class, 'update']);
        Route::delete('/{permission}', [PermissionApiController::class, 'destroy']);
    });

}); // End of FrontendApiGuard middleware group
