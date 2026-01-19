<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

class ChatMessageReceived extends Notification
{
    use Queueable;

    /** @var string */
    protected string $fromName;
    /** @var string */
    protected string $body;
    /** @var int|string */
    protected $conversationId;
    /** @var string|null */
    protected ?string $avatar;

    public function __construct(string $fromName, string $body, int|string $conversationId, ?string $avatar = null)
    {
        $this->fromName = $fromName;
        $this->body = $body;
        $this->conversationId = $conversationId;
        $this->avatar = $avatar;
    }

    public function via($notifiable): array
    {
        $type = 'chat_message_received';
        // Resolve preference service only if available to avoid container errors
        $prefSvcClass = 'App\\Services\\UserNotificationPreferenceService';
        if (class_exists($prefSvcClass) && app()->bound($prefSvcClass)) {
            /** @var object $svc */
            $svc = app($prefSvcClass);
            if (method_exists($svc, 'isMuted') && $svc->isMuted($notifiable, $type)) {
                return [];
            }
        }
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        // Determine chat URL safely (use named route when available, fallback to a sensible URL)
        $baseUrl = Route::has('dashboard.chating')
            ? route('dashboard.chating', [], false)
            : url('/dashboard/chat');
        $url = $baseUrl . '?conversation=' . $this->conversationId;

        // Ensure avatar is an absolute URL
        $avatar = $this->avatar;
        if (empty($avatar)) {
            $avatar = asset('assets/img/avatars/4.png');
        } elseif (!preg_match('/^https?:\/\//i', $avatar)) {
            $avatar = asset(ltrim($avatar, '/'));
        }

        return [
            'title'            => 'رسالة جديدة من ' . $this->fromName,
            'body'             => $this->body,
            // alias for some views expecting 'message'
            'message'          => $this->body,
            'conversation_id'  => $this->conversationId,
            'avatar'           => $avatar,
            'url'              => $url,
            // icon info for navbar template
            'icon'             => 'ti tabler-message',
            'icon_class'       => 'bg-primary',
        ];
    }
}
