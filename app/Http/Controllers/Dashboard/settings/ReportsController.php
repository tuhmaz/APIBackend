<?php

namespace App\Http\Controllers\Dashboard\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Report;
use App\Models\Conversation;
use App\Models\User;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with(['reporter:id,name','reported:id,name'])
            ->latest();

        // فلاتر اختيارية
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->query('q')) {
            $query->where(function($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%");
            });
        }

        $reports = $query->paginate(15)->withQueryString();

        return view('content.dashboard.settings.reports', compact('reports'));
    }

    // إرجاع تفاصيل التقرير JSON لعرضها في نافذة منبثقة
    public function show(Report $report)
    {
        $report->load(['reporter:id,name,email', 'reported:id,name,email']);
        return response()->json([
            'id' => $report->id,
            'reason' => $report->reason,
            'status' => $report->status,
            'created_at' => $report->created_at?->toDateTimeString(),
            'reporter' => [
                'id' => $report->reporter?->id,
                'name' => $report->reporter?->name,
                'email' => $report->reporter?->email,
            ],
            'reported' => [
                'id' => $report->reported?->id,
                'name' => $report->reported?->name,
                'email' => $report->reported?->email,
            ],
        ]);
    }

    // إرسال رسالة للمستخدم المُبلَّغ عنه عبر محادثة خاصة
    public function message(Request $request, Report $report)
    {
        $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $admin = Auth::user();
        $toUser = User::findOrFail($report->reported_user_id);

        // ابحث أو أنشئ محادثة خاصة بين الأدمن وهذا المستخدم
        $conversation = Conversation::where('type', 'private')
            ->whereHas('users', function ($q) use ($admin) {
                $q->where('users.id', $admin->id);
            })
            ->whereHas('users', function ($q) use ($toUser) {
                $q->where('users.id', $toUser->id);
            })
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'type' => 'private',
                'title' => null,
            ]);
            // إرفاق الطرفين إلى المحادثة
            $conversation->users()->attach([$admin->id, $toUser->id]);
        }

        // إنشاء الرسالة وفق مخطط جدول messages
        $message = $conversation->messages()->create([
            'sender_id' => $admin->id,
            'subject'   => '',
            'body'      => $request->body,
            'is_chat'   => true,
        ]);

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
        ]);
    }

    // تحديث حالة التقرير (pending | reviewed | complet)
    public function updateStatus(Request $request, Report $report)
    {
        $request->validate([
            'status' => 'required|string|in:pending,reviewed,complet',
        ]);

        $report->status = $request->status;
        $report->save();

        return response()->json([
            'success' => true,
            'status' => $report->status,
        ]);
    }
}
