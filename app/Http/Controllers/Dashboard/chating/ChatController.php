<?php

namespace App\Http\Controllers\Dashboard\chating;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\ChatMessageReceived;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChatController extends Controller
{
    // get all conversations (private and public) for the current user
    public function conversations()
    {
        $user = Auth::user();

        // Ensure at least one public conversation exists
        if (!Conversation::where('type', 'public')->exists()) {
            $uid = Auth::id() ?: User::query()->value('id');
            Conversation::create([
                'type' => 'public',
                'title' => 'الدردشة العامة',
                'user1_id' => $uid,
                'user2_id' => $uid,
            ]);
        }

        // all public conversations or those the user is a part of
        $conversations = Conversation::where('type', 'public')
            ->orWhereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->with([
                'users:id,name',
                'messages' => function($q) {
                    $q->where('is_chat', true)->latest()->limit(1); // آخر رسالة دردشة فقط
                },
                'messages.sender:id,name,profile_photo_path'
            ])
            ->get();

        return response()->json([
            'conversations' => $conversations,
        ]);
    }

    // get messages for a specific conversation
    public function messages($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // access control: user must be a part of the conversation or it must be public
        if ($conversation->type == 'private' && !$conversation->users->contains(Auth::id())) {
            abort(403, 'Unauthorized');
        }

        // دعم تحميل رسائل جديدة فقط باستخدام بارامتر after (id آخر رسالة)
        $after = request()->query('after');

        $messages = $conversation->messages()
            ->where('is_chat', true)
            ->when($after, function ($q) use ($after) {
                // نفترض زيادة id بترتيب الإدخال، وإن لم يكن يمكن التحويل إلى created_at
                $q->where('id', '>', (int) $after);
            })
            ->with('sender:id,name,profile_photo_path')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'messages' => $messages,
        ]);
    }

    // send a message to a conversation
    public function sendMessage(Request $request, $conversationId)
    {
        $request->validate([
            'body' => 'required|string|max:2000',
            'subject' => 'nullable|string|max:255',
        ]);

        $conversation = Conversation::findOrFail($conversationId);

// التأكد من صلاحية المستخدم
if ($conversation->type == 'private' && !$conversation->users->contains(Auth::id())) {
    abort(403, 'Unauthorized');
}

// BLOCK CHECK
if ($conversation->type === 'private') {
    $currentUser = Auth::user();
    $toUserId = $conversation->users()->where('users.id', '!=', $currentUser->id)->first()?->id;
    if ($toUserId) {
        if (\App\Models\ChatBlock::where('blocker_id', $currentUser->id)->where('blocked_id', $toUserId)->exists() ||
            \App\Models\ChatBlock::where('blocker_id', $toUserId)->where('blocked_id', $currentUser->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'لا يمكن إرسال الرسائل، يوجد حظر بين الطرفين.'], 403);
        }
    }
}

// حفظ الرسالة
$message = $conversation->messages()->create([
    'sender_id'       => Auth::id(),
    'subject'         => $request->subject ?? '',
    'body'            => $request->body,
    'is_chat'         => true,
]);

$message->load('sender:id,name');

// إرسال إشعار لجميع المشاركين باستثناء المرسل
$receivers = $conversation->users()->where('users.id', '!=', Auth::id())->get();
foreach ($receivers as $user) {
    $user->notify(new \App\Notifications\ChatMessageReceived(Auth::user()->name, $request->body, $conversation->id));
}

return response()->json([
    'message' => $message,
]); 
    }

    // create or get a private conversation between users
    public function createPrivateConversation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user1 = Auth::id();
        $user2 = $request->user_id;

        // check if the private conversation exists
        $conversation = Conversation::where('type', 'private')
            ->whereHas('users', function ($q) use ($user1) {
                $q->where('users.id', $user1);
            })
            ->whereHas('users', function ($q) use ($user2) {
                $q->where('users.id', $user2);
            })
            ->first();

        // if the private conversation does not exist, create a new one
        if (!$conversation) {
            DB::beginTransaction();
            $conversation = Conversation::create([
                'type' => 'private',
                'user1_id' => $user1,
                'user2_id' => $user2,
            ]);
            $conversation->users()->attach([$user1, $user2]);
            DB::commit();
        }

        return response()->json([
            'conversation_id' => $conversation->id,
        ]);
    }

    // إحضار/إنشاء المحادثة العامة الافتراضية وإرجاعها
    public function publicConversation(Request $request)
    {
        $conversation = Conversation::where('type', 'public')
            ->where('title', 'الدردشة العامة')
            ->first();

        if (!$conversation) {
            $uid = Auth::id() ?: User::query()->value('id');
            $conversation = Conversation::create([
                'type' => 'public',
                'title' => 'الدردشة العامة',
                'user1_id' => $uid,
                'user2_id' => $uid,
            ]);
        }

        // إرفاق المستخدم الحالي اختيارياً لضمان ظهورها ضمن علاقات العضوية مستقبلاً
        $uid = Auth::id();
        if ($uid && !$conversation->users()->where('users.id', $uid)->exists()) {
            $conversation->users()->attach($uid);
        }

        return response()->json([
            'id' => $conversation->id,
            'title' => $conversation->title,
        ]);
    }

    // create a public conversation (once only)
    public function createPublicConversation(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        // check if there is a public conversation with the same name
        $existing = Conversation::where('type', 'public')
            ->where('title', $request->title)
            ->first();

        if ($existing) {
            return response()->json([
                'conversation_id' => $existing->id,
            ]);
        }

        $uid = Auth::id() ?: User::query()->value('id');
        $conversation = Conversation::create([
            'type' => 'public',
            'title' => $request->title,
            'user1_id' => $uid,
            'user2_id' => $uid,
        ]);

        return response()->json([
            'conversation_id' => $conversation->id,
        ]);
    }

    // جلب جميع المستخدمين (عدا المستخدم الحالي) مع دعم البحث والترقيم وفرز المتصلين أولاً
    public function users(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        // حماية الحد الأقصى للصفحة
        $perPage = (int) $request->query('per_page', 50);
        if ($perPage < 10) $perPage = 10;
        if ($perPage > 100) $perPage = 100;

        $hasIsOnline = Schema::hasColumn('users', 'is_online');
        $activityWindowMinutes = (int) $request->query('activity_window', 5); // نافذة اعتبار المستخدم متصلاً

        // تحضير استعلام الجلسات النشطة كـ left join فرعي
        $vsSub = DB::table('visitor_sessions')
            ->select('user_id', DB::raw('MAX(last_activity) as last_activity'))
            ->where('last_activity', '>=', now()->subMinutes($activityWindowMinutes))
            ->groupBy('user_id');

        $query = \App\Models\User::query()
            ->from('users')
            ->where('id', '!=', Auth::id())
            ->leftJoinSub($vsSub, 'vs', function ($join) {
                $join->on('vs.user_id', '=', 'users.id');
            })
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.profile_photo_path',
                // إذا كان لدينا عمود is_online خذه، وإلا احسبه من vs
                DB::raw(($hasIsOnline ? 'users.is_online' : 'CASE WHEN vs.user_id IS NULL THEN 0 ELSE 1 END') . ' as is_online'),
            ]);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        // فلترة المتصلين فقط عند الطلب
        if ($request->boolean('online')) {
            if ($hasIsOnline) {
                $query->where('users.is_online', true);
            } else {
                $query->whereNotNull('vs.user_id');
            }
        }

        // المتصلون أولاً ثم بالاسم تصاعدي
        $query->orderByDesc(DB::raw($hasIsOnline ? 'users.is_online' : '(vs.user_id IS NOT NULL)'));
        $query->orderBy('name');

        $paginator = $query->paginate($perPage);

        $users = collect($paginator->items())->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_online' => (bool) ($user->is_online ?? false),
                'avatar' => $user->profile_photo_url ?? asset('assets/img/avatars/4.png'),
            ];
        });

        return response()->json([
            'users' => $users,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'has_more' => $paginator->hasMorePages(),
                'next_page' => $paginator->hasMorePages() ? ($paginator->currentPage() + 1) : null,
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function getUser($id)
    {
        $user = \App\Models\User::findOrFail($id);
        // مثال: لديك عمود is_online في users أو تحسبها من جدول آخر
        // هنا مثال إذا كان لديك حقل is_online، إذا لا، استبدلها بالمنطق الذي تعتمد عليه
        return response()->json([
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'avatar'    => $user->avatar,
            'about'     => $user->about ?? '',
            'role'      => $user->role ?? '',
            'is_online' => (bool) $user->is_online // عدّل حسب منطقك في معرفة الحالة
        ]);
    }
    public function unreadCounts(Request $request)
    {
        $userId = Auth::id();
        $current = $request->query('current'); // محادثة مفتوحة حالياً (اختياري)

        // نظراً لعدم وجود أعمدة read_at / receiver_id في المخطط الحالي،
        // سنعتمد على مبدأ مبسط: نعدّ الرسائل الواردة من "الطرف الآخر" في كل محادثة يشارك فيها المستخدم.
        // هذا لا يمثل "غير مقروء" الحقيقي لكنه مؤشر كافٍ لعرض الجرس إلى أن نضيف تتبّع actual read.

        // conversations التي يشارك فيها المستخدم
        $conversationIds = DB::table('conversation_user')
            ->where('user_id', $userId)
            ->pluck('conversation_id');

        if ($conversationIds->isEmpty()) {
            return response()->json([]);
        }

        // عدّ الرسائل في كل محادثة التي كتبها "غير المستخدم الحالي"
        $rows = Message::query()
            ->selectRaw('conversation_id, COUNT(*) as cnt')
            ->whereIn('conversation_id', $conversationIds)
            ->where('is_chat', true)
            ->where('sender_id', '!=', $userId)
            ->groupBy('conversation_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(string)$row->conversation_id] = (int)$row->cnt;
        }

        // إذا تم تمرير محادثة حالية افتُتحت، يمكن تصفيرها حتى لا يظهر الجرس لها
        if ($current && isset($map[$current])) {
            $map[$current] = 0;
        }

        return response()->json($map);
    }
}
