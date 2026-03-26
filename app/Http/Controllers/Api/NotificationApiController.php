<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\NotificationBulkActionRequest;
use App\Http\Resources\Api\NotificationResource;
use App\Http\Resources\BaseResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationApiController extends Controller
{
    /**
     * List notifications (paginated)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return (new BaseResource(['message' => 'Unauthenticated']))
                ->response($request)
                ->setStatusCode(401);
        }

        $perPage = (int) $request->query('per_page', 10);
        if ($perPage < 5)  $perPage = 5;
        if ($perPage > 50) $perPage = 50;

        $page = (int) $request->query('page', 1);

        // Cache notifications for short time
        $cacheKey = "user_notifications_{$user->id}_page_{$page}_per_{$perPage}";
        $paginator = Cache::remember($cacheKey, 15, function () use ($user, $perPage) {
            return $user->notifications()
                ->select('id', 'type', 'data', 'read_at', 'created_at')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->withQueryString();
        });

        // Cache unread count separately
        $unreadCount = $this->getCachedUnreadCount($user);

        return NotificationResource::collection($paginator)
            ->additional([
                'success' => true,
                'unread_count' => $unreadCount,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ]);
    }

    /**
     * Latest notifications (for navbar bell polling)
     */
    public function latest(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return (new BaseResource(['message' => 'Unauthenticated']))
                ->response($request)
                ->setStatusCode(401);
        }

        $limit = (int) $request->query('limit', 10);
        if ($limit < 1)  $limit = 1;
        if ($limit > 50) $limit = 50;

        // Cache latest notifications for 10 seconds (polling optimization)
        $cacheKey = "user_notifications_latest_{$user->id}_limit_{$limit}";
        $notifications = Cache::remember($cacheKey, 10, function () use ($user, $limit) {
            return $user->notifications()
                ->select('id', 'type', 'data', 'read_at', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });

        // Cache unread count
        $unreadCount = $this->getCachedUnreadCount($user);

        return NotificationResource::collection($notifications)
            ->additional([
                'success' => true,
                'unread_count' => $unreadCount,
            ]);
    }

    /**
     * Get cached unread notification count
     */
    private function getCachedUnreadCount($user): int
    {
        $cacheKey = "user_unread_notifications_count_{$user->id}";
        return (int) Cache::remember($cacheKey, 15, function () use ($user) {
            return $user->unreadNotifications()->count();
        });
    }

    /**
     * Clear notification cache for user
     */
    private function clearNotificationCache($user): void
    {
        Cache::forget("user_unread_notifications_count_{$user->id}");
        // Clear latest notifications cache
        for ($i = 1; $i <= 50; $i++) {
            Cache::forget("user_notifications_latest_{$user->id}_limit_{$i}");
        }
        // Clear paginated cache (first 10 pages)
        for ($page = 1; $page <= 10; $page++) {
            for ($perPage = 5; $perPage <= 50; $perPage += 5) {
                Cache::forget("user_notifications_{$user->id}_page_{$page}_per_{$perPage}");
            }
        }
    }

    /**
     * Mark single notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return (new BaseResource(['message' => 'Unauthenticated']))
                ->response($request)
                ->setStatusCode(401);
        }

        $notification = $user->notifications()->find($id);
        if (!$notification) {
            return (new BaseResource(['message' => 'Notification not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        $notification->markAsRead();

        // Clear cache
        $this->clearNotificationCache($user);

        return new BaseResource([
            'message'      => 'Notification marked as read',
            'unread_count' => (int) $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return (new BaseResource(['message' => 'Unauthenticated']))
                ->response($request)
                ->setStatusCode(401);
        }

        $user->unreadNotifications()->update(['read_at' => now()]);

        // Clear cache
        $this->clearNotificationCache($user);

        return new BaseResource([
            'message'      => 'All notifications marked as read',
            'unread_count' => 0,
        ]);
    }

    /**
     * Bulk action: delete or mark-as-read
     */
    public function bulkAction(NotificationBulkActionRequest $request)
    {
        $user = $request->user();
        if (!$user) {
            return (new BaseResource(['message' => 'Unauthenticated']))
                ->response($request)
                ->setStatusCode(401);
        }

        $validated = $request->validated();

        $ids    = $validated['ids'];
        $action = $validated['action'];

        $query = $user->notifications()->whereIn('id', $ids);

        if ($action === 'delete') {
            $deleted = $query->delete();

            // Clear cache
            $this->clearNotificationCache($user);

            return new BaseResource([
                'message' => 'Notifications deleted successfully',
                'deleted' => $deleted,
            ]);
        }

        if ($action === 'mark-as-read') {
            $updated = $query->update(['read_at' => now()]);

            // Clear cache
            $this->clearNotificationCache($user);

            return new BaseResource([
                'message'      => 'Notifications marked as read',
                'updated'      => $updated,
                'unread_count' => (int) $user->unreadNotifications()->count(),
            ]);
        }

        return (new BaseResource(['message' => 'Invalid action']))
            ->response($request)
            ->setStatusCode(422);
    }

    /**
     * Delete single notification
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return (new BaseResource(['message' => 'Unauthenticated']))
                ->response($request)
                ->setStatusCode(401);
        }

        $notification = $user->notifications()->find($id);
        if (!$notification) {
            return (new BaseResource(['message' => 'Notification not found']))
                ->response($request)
                ->setStatusCode(404);
        }

        $notification->delete();

        // Clear cache
        $this->clearNotificationCache($user);

        return new BaseResource([
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * Broadcast a new notification to all Admin and Supervisor users.
     * Called from the frontend after article/post creation or update.
     * Uses a single bulk INSERT for efficiency.
     */
    public function store(Request $request)
    {
        $currentUser = $request->user();
        if (!$currentUser) {
            return (new BaseResource(['message' => 'Unauthenticated']))
                ->response($request)
                ->setStatusCode(401);
        }

        $validated = $request->validate([
            'type'       => 'required|string|max:100',
            'title'      => 'required|string|max:255',
            'message'    => 'required|string|max:500',
            'action_url' => 'nullable|string|max:500',
        ]);

        // Send to all Admin and Supervisor users (dashboard members)
        $recipients = User::role(['Admin', 'Supervisor'])
            ->select('id')
            ->get();

        if ($recipients->isEmpty()) {
            return new BaseResource([
                'success' => true,
                'message' => 'No recipients found',
                'sent_to' => 0,
            ]);
        }

        $now  = now();
        $data = json_encode([
            'title'      => $validated['title'],
            'message'    => $validated['message'],
            'action_url' => $validated['action_url'] ?? null,
        ]);

        // Build bulk insert rows — one notification per admin/supervisor
        $rows = $recipients->map(fn($user) => [
            'id'              => Str::uuid()->toString(),
            'type'            => $validated['type'],
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id'   => $user->id,
            'data'            => $data,
            'read_at'         => null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ])->all();

        DB::table('notifications')->insert($rows);

        // Clear notification cache for each recipient
        foreach ($recipients as $user) {
            $this->clearNotificationCache($user);
        }

        return new BaseResource([
            'success' => true,
            'message' => 'Notification sent successfully',
            'sent_to' => count($rows),
        ]);
    }

    /**
     * Prune old notifications from all databases
     * Requires 'manage settings' permission
     */
    public function prune(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return (new BaseResource(['message' => 'Unauthenticated']))
                ->response($request)
                ->setStatusCode(401);
        }

        // Check permission
        if (!$user->can('manage settings')) {
            return (new BaseResource(['message' => 'غير مصرح لك بهذا الإجراء']))
                ->response($request)
                ->setStatusCode(403);
        }

        $days = (int) $request->input('days', 3);
        if ($days < 1) {
            $days = 1;
        }

        $readOnly = (bool) $request->input('read_only', false);

        try {
            // Use the static method from PruneNotifications command
            $result = \App\Console\Commands\PruneNotifications::pruneOldNotifications($days, $readOnly);

            return new BaseResource([
                'success' => true,
                'message' => sprintf(
                    'تم حذف %d إشعار أقدم من %d أيام',
                    $result['total_deleted'],
                    $days
                ),
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return (new BaseResource([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الإشعارات: ' . $e->getMessage(),
            ]))->response($request)->setStatusCode(500);
        }
    }
}
