<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class MessageNotification extends Notification
{
    use Queueable;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');

        return [
            'title' => 'New Message from ' . $this->message->sender->name,
            'message_id' => $this->message->id,
            'url' => $frontendUrl . '/dashboard/messages?message=' . $this->message->id,
        ];
    }
}
