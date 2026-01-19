<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;


class NotificationController extends Controller
{
    public function index()
    {
        // جلب الإشعارات مع التصفية والتقسيم
        $user = Auth::user();
        /** @var User $user */
        $notifications = $user->notifications()->paginate(10);

        return view('content.dashboard.notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $user = Auth::user();
        /** @var User $user */
        $notification = $user->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
        }

        return redirect()->back();
    }

    public function markAllAsRead()
    {
        $user = Auth::user();
        /** @var User $user */
        $user->unreadNotifications->markAsRead();

        return redirect()->back();
    }

    public function deleteSelected(Request $request)
    {
        $request->validate([
            'selected_notifications' => 'required|array',
        ]);

        // الحصول على إشعارات المستخدم الحالي
        $user = Auth::user();
        /** @var User $user */
        $user->notifications()->whereIn('id', $request->selected_notifications)->delete();

        return redirect()->back()->with('success', __('notifications.bulk_deleted'));
    }

    public function handleActions(Request $request)
    {
        $request->validate([
            'selected_notifications' => 'required|array',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $action = $request->input('action');

        if ($action == 'delete') {
            $user->notifications()->whereIn('id', $request->selected_notifications)->delete();
            return redirect()->back()->with('success', __('notifications.bulk_deleted'));
        }

        if ($action == 'mark-as-read') {
            $user->notifications()->whereIn('id', $request->selected_notifications)->update(['read_at' => now()]);
            return redirect()->back()->with('success', __('notifications.bulk_marked_read'));
        }

        return redirect()->back()->with('error', __('notifications.invalid_action'));
    }

    public function delete($id)
    {
        $user = Auth::user();
        /** @var User $user */
        $notification = $user->notifications()->find($id);
        if ($notification) {
            $notification->delete();
        }

        return redirect()->back()->with('success', __('notifications.deleted_single'));
    }

    /**
     * Return latest notifications as JSON for navbar bell (web session auth)
     */
    public function json(Request $request)
    {
        $user = Auth::user();
        /** @var User $user */
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $limit = (int) $request->query('limit', 10);
        if ($limit < 1) $limit = 1;
        if ($limit > 50) $limit = 50;

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => (string) $n->id,
                    'type' => $n->type,
                    'data' => $n->data,
                    'read_at' => $n->read_at ? $n->read_at->format('Y-m-d H:i:s') : null,
                    'created_at' => $n->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $notifications,
            'unread_count' => (int) $user->unreadNotifications()->count(),
        ]);
    }
}
