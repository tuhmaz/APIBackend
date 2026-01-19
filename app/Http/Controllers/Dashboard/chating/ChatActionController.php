<?php

namespace App\Http\Controllers\Dashboard\chating;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChatBlock;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Notifications\ChatMessageReceived;

class ChatActionController extends Controller
{
    /**
     * حظر مستخدم من الدردشة (لا يمكن إرسال أو استقبال رسائل معه)
     */
    public function blockChat($id)
    {
        $blockerId = Auth::id();
        if ($blockerId == $id) {
            return response()->json(['success' => false, 'message' => 'لا يمكنك حظر نفسك!']);
        }
        $block = ChatBlock::firstOrCreate([
            'blocker_id' => $blockerId,
            'blocked_id' => $id
        ]);
        return response()->json(['success' => true, 'message' => 'تم حظر العضو من الدردشة.']);
    }

    /**
     * فك الحظر عن مستخدم في الدردشة
     */
    public function unblockChat($id)
    {
        $blockerId = Auth::id();
        ChatBlock::where([
            'blocker_id' => $blockerId,
            'blocked_id' => $id
        ])->delete();
        return response()->json(['success' => true, 'message' => 'تم فك الحظر عن المستخدم.']);
    }

    /**
     * التحقق من حالة الحظر بين المستخدمين (للعرض في الواجهة)
     */
    public function checkBlockStatus($id)
    {
        $userId = Auth::id();
        $iBlocked = ChatBlock::where('blocker_id', $userId)->where('blocked_id', $id)->exists();
        $blockedMe = ChatBlock::where('blocker_id', $id)->where('blocked_id', $userId)->exists();

        return response()->json([
            'i_blocked'   => $iBlocked,
            'blocked_me'  => $blockedMe,
        ]);
    }

    /**
     * إرسال رسالة (مع مراعاة الحظر)
     */
    public function sendMessage(Request $request, $conversationId)
    {
        $request->validate([
            'body' => 'required|string|max:2000',
            'subject' => 'nullable|string|max:255',
        ]);

        $conversation = Conversation::findOrFail($conversationId);
        $currentUser = Auth::user();

        // تحديد الـ ID للطرف الآخر (خاص بالمحادثات الخاصة)
        $toUserId = null;
        if ($conversation->type === 'private') {
            $toUserId = $conversation->users()->where('users.id', '!=', $currentUser->id)->first()?->id;
        }

        // التحقق من الحظر
        if ($toUserId) {
            // إذا أنا حاجب العضو أو العضو حاجبني
            if (ChatBlock::where('blocker_id', $currentUser->id)->where('blocked_id', $toUserId)->exists()) {
                return response()->json(['success' => false, 'message' => 'لقد قمت بحظر هذا العضو من الدردشة!'], 403);
            }
            if (ChatBlock::where('blocker_id', $toUserId)->where('blocked_id', $currentUser->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'هذا العضو قام بحظرك من الدردشة!'], 403);
            }
        }

        // إذا لم يكن هنالك حظر
        $message = $conversation->messages()->create([
            'sender_id' => $currentUser->id,
            'subject'   => $request->subject ?? '',
            'body'      => $request->body,
            'is_chat'   => true,
        ]);

        $message->load('sender:id,name');

        // إرسال إشعار لجميع المشاركين باستثناء المرسل (لصندوق الإشعارات)
        try {
            $receivers = $conversation->users()->where('users.id', '!=', $currentUser->id)->get();
            foreach ($receivers as $user) {
                $user->notify(new ChatMessageReceived($currentUser->name, $request->body, $conversation->id, $currentUser->profile_photo_path ?? null));
            }
        } catch (\Throwable $e) {
            Log::warning('chat notify failed (ChatActionController)', ['error' => $e->getMessage()]);
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    /**
     * مسح سجل المحادثة لكلا الطرفين
     */
    public function clearChat($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        $currentUser = Auth::user();

        // إذا كانت المحادثة عامة، فقط الأدمن يستطيع مسحها
        if ($conversation->type === 'public') {
            // فحص مرن لدور الأدمن بدون الاعتماد على دالة معينة
            $isAdmin = false;
            $isSuper = function ($name) {
                if (!is_string($name) || $name === '') return false;
                $n = strtolower($name);
                // طبيع اسم الدور بإزالة/استبدال الفواصل والمسافات
                $n = preg_replace('/[^a-z0-9]+/', '_', $n);
                $n = trim($n, '_');
                return in_array($n, ['super_admin', 'superadmin']);
            };
            if ($currentUser) {
                // سجّل معلومات أولية للتشخيص
                try {
                    $dbg = [
                        'user_id' => $currentUser->id ?? null,
                        'role' => isset($currentUser->role) ? (is_string($currentUser->role) ? $currentUser->role : ($currentUser->role->name ?? null)) : null,
                        'role_id' => $currentUser->role_id ?? null,
                    ];
                    if (isset($currentUser->roles)) {
                        $rolesProp = $currentUser->roles;
                        if ($rolesProp instanceof \Illuminate\Support\Collection) {
                            $dbg['roles'] = $rolesProp->map(function($r){ return is_string($r) ? $r : ($r->name ?? null); })->filter()->values();
                        } elseif (is_array($rolesProp)) {
                            $dbg['roles'] = collect($rolesProp)->map(function($r){ return is_string($r) ? $r : ($r->name ?? null); })->filter()->values();
                        }
                    }
                    Log::info('clearChat public role debug', $dbg);
                } catch (\Throwable $e) {}
                // لا تعتمد على دوال Spatie لتجنّب تحذيرات اللينتر، سنستخدم الخصائص/العلاقات مباشرة

                // إذا كان هناك علاقة role واحدة كخاصية مباشرة
                if (!$isAdmin && isset($currentUser->role)) {
                    $roleName = is_string($currentUser->role) ? $currentUser->role : ($currentUser->role->name ?? null);
                    if ($isSuper($roleName)) {
                        $isAdmin = true;
                    }
                }

                // إذا كان هناك أدوار متعددة (Collection أو array)
                if (!$isAdmin && isset($currentUser->roles)) {
                    $rolesProp = $currentUser->roles;
                    if ($rolesProp instanceof \Illuminate\Support\Collection) {
                        foreach ($rolesProp as $r) {
                            $rName = is_string($r) ? $r : ($r->name ?? null);
                            if ($isSuper($rName)) { $isAdmin = true; break; }
                        }
                    } elseif (is_array($rolesProp) || $rolesProp instanceof \Traversable) {
                        foreach ($rolesProp as $r) {
                            $rName = is_string($r) ? $r : ($r->name ?? null);
                            if ($isSuper($rName)) { $isAdmin = true; break; }
                        }
                    }
                }

                // فحص role_id كحل أخير (مثلاً 1 = super_admin بحسب ترميزكم)
                if (!$isAdmin && isset($currentUser->role_id) && (int) $currentUser->role_id === 1) {
                    $isAdmin = true;
                }
            }

            if (!$isAdmin) {
                Log::warning('clearChat denied for public chat (not super_admin)', [
                    'user_id' => $currentUser->id ?? null,
                    'conversation_id' => $conversationId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'غير مسموح إلا للمشرف بمسح محادثة الدردشة العامة.'
                ], 403);
            }

            // الأدمن فقط: مسح جميع رسائل الدردشة العامة
            $conversation->messages()->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف جميع الرسائل في هذه المحادثة بنجاح.'
            ]);
        }

        // التحقق من أن المستخدم مشارك في المحادثة
        if (!$conversation->users->contains($currentUser->id)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتنفيذ هذه العملية.'
            ], 403);
        }

        // مسح جميع رسائل المحادثة
        $deletedCount = $conversation->messages()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف جميع الرسائل في هذه المحادثة بنجاح.'
        ]);
    }

    /**
     * إخفاء (حذف من قائمتي فقط) محادثة خاصة
     */
    public function hideConversation($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        $currentUser = Auth::user();

        // لا يُسمح بإخفاء الدردشة العامة من القائمة
        if ($conversation->type === 'public') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إخفاء الدردشة العامة.'
            ], 422);
        }

        // يجب أن يكون المستخدم مشاركًا في هذه المحادثة
        if (!$conversation->users->contains($currentUser->id)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتنفيذ هذه العملية.'
            ], 403);
        }

        // إزالة المستخدم الحالي من المحادثة (pivot)
        $conversation->users()->detach($currentUser->id);

        return response()->json([
            'success' => true,
            'message' => 'تم إخفاء المحادثة من قائمتك.'
        ]);
    }

    /**
     * الإبلاغ عن مستخدم بسبب إساءة استخدام
     */
    public function reportAbuse(Request $request, $userId)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $reporterId = Auth::id();

        // التحقق من عدم التكرار
        $existingReport = \App\Models\Report::where('reporter_id', $reporterId)
            ->where('reported_user_id', $userId)
            ->first();

        if ($existingReport) {
            // نُعيد 200 مع success=false لتفادي ظهور خطأ 400 في الواجهة
            return response()->json([
                'success' => false,
                'message' => 'لقد قمت مسبقاً بالإبلاغ عن هذا المستخدم.'
            ]);
        }

        // إنشاء تقرير جديد
        \App\Models\Report::create([
            'reporter_id' => $reporterId,
            'reported_user_id' => $userId,
            'reason' => $request->reason,
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم الإبلاغ عن المستخدم بنجاح وسيتم مراجعة التقرير من قبل الإدارة.'
        ]);

    }

}
