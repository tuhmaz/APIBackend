<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Notifications\MessageNotification;
use Illuminate\Support\Str;
use App\Http\Resources\Api\MessageResource;
use App\Http\Resources\BaseResource;

class MessageApiController extends Controller
{
    /**
     * Inbox Messages
     */
    public function inbox()
    {
        $userId = Auth::id();

        $conversationIds = Conversation::where('user1_id', $userId)
            ->orWhere('user2_id', $userId)->pluck('id');

        $messages = Message::with(['sender', 'conversation.user1', 'conversation.user2'])
            ->whereIn('conversation_id', $conversationIds)
            ->where('is_chat', false)
            ->where('sender_id', '!=', $userId)
            ->latest()
            ->paginate(20);

        return MessageResource::collection($messages)
            ->additional(['success' => true]);
    }

    /**
     * Sent Messages
     */
    public function sent()
    {
        $messages = Message::with(['conversation.user1', 'conversation.user2'])
            ->where('sender_id', Auth::id())
            ->where('is_chat', false)
            ->latest()
            ->paginate(20);

        return MessageResource::collection($messages)
            ->additional(['success' => true]);
    }

    /**
     * Draft messages
     */
    public function drafts()
    {
        $userId = Auth::id();

        $drafts = Message::where('sender_id', $userId)
            ->where('is_chat', false)
            ->where('is_draft', true)
            ->latest()
            ->paginate(20);

        return MessageResource::collection($drafts)
            ->additional(['success' => true]);
    }

    /**
     * Send Message
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string'
        ]);

        $sender = Auth::id();
        $recipient = $validated['recipient_id'];

        $conversation = Conversation::firstOrCreate([
            'user1_id' => min($sender, $recipient),
            'user2_id' => max($sender, $recipient)
        ]);

        $message = Message::create([
            'sender_id' => $sender,
            'conversation_id' => $conversation->id,
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'is_chat' => false,
            'read' => false
        ]);

        User::find($recipient)->notify(new MessageNotification($message));

        return (new MessageResource($message))
            ->additional(['message' => "Message sent!"]);
    }

    /**
     * Save draft
     */
    public function saveDraft(Request $request)
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'subject' => 'nullable|string',
            'body' => 'nullable|string',
        ]);

        $sender = Auth::id();
        $recipient = $validated['recipient_id'];

        $conversation = Conversation::firstOrCreate([
            'user1_id' => min($sender, $recipient),
            'user2_id' => max($sender, $recipient)
        ]);

        $message = Message::create([
            'sender_id' => $sender,
            'conversation_id' => $conversation->id,
            'subject' => $validated['subject'] ?? '',
            'body' => $validated['body'] ?? '',
            'is_draft' => true,
            'is_chat' => false,
            'read' => false,
        ]);

        return (new MessageResource($message))
            ->additional(['message' => "Draft saved"]);
    }

    /**
     * Mark as read
     */
    public function markAsRead($id)
    {
        $message = Message::findOrFail($id);

        if (
            $message->sender_id !== Auth::id() &&
            $message->conversation->hasUser(Auth::id())
        ) {
            $message->update(['read' => true]);
        }

        return new BaseResource([]);
    }

    /**
     * Toggle important
     */
    public function toggleImportant($id)
    {
        $message = Message::findOrFail($id);

        if ($message->sender_id !== Auth::id()) {
            return (new BaseResource(['message' => 'Unauthorized']))
                ->response(request())
                ->setStatusCode(403);
        }

        $message->is_important = !$message->is_important;
        $message->save();

        return new BaseResource(['important' => $message->is_important]);
    }

    /**
     * Delete a message
     */
    public function destroy($id)
    {
        $message = Message::findOrFail($id);

        // Check if user is the sender or a participant in the conversation
        $isSender = $message->sender_id == Auth::id();
        $isParticipant = $message->conversation && $message->conversation->hasUser(Auth::id());

        if (!$isSender && !$isParticipant) {
            return (new BaseResource(['message' => 'Unauthorized']))
                ->response(request())
                ->setStatusCode(403);
        }

        $message->delete();

        return new BaseResource(['message' => 'Message deleted']);
    }

    /**
     * Show a single message
     */
    public function show($id)
    {
        $message = Message::with('sender')
            ->where('id', $id)
            ->firstOrFail();

        $isSender = $message->sender_id == Auth::id();
        $isParticipant = $message->conversation && $message->conversation->hasUser(Auth::id());

        if (!$isSender && !$isParticipant) {
            return (new BaseResource(['message' => 'Unauthorized']))
                ->response(request())
                ->setStatusCode(403);
        }

        return new MessageResource($message);
    }
}
