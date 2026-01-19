<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostNotification extends Notification
{
    use Queueable;

    public $post;

    public function __construct($post)
    {
        $this->post = $post;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $url = '/dashboard/posts/' . $this->post->id;
        
        return [
            'title' => 'منشور جديد',
            'message' => 'تم نشر منشور جديد: ' . $this->post->title,
            'post_id' => $this->post->id,
            'type' => 'Post',
            'url' => $url,
            'action_url' => $url
        ];
    }
}
