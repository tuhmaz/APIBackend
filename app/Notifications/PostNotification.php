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
        $countryCode = $this->resolveCountryCode();
        $url = "/{$countryCode}/posts/{$this->post->id}";

        return [
            'title' => 'منشور جديد: ' . $this->post->title,
            'message' => 'تم نشر منشور جديد.',
            'post_id' => $this->post->id,
            'type' => 'post',
            'url' => $url,
            'action_url' => $url,
        ];
    }

    private function resolveCountryCode(): string
    {
        $country = strtolower((string) ($this->post->country ?? ''));

        if (in_array($country, ['jo', 'sa', 'eg', 'ps'], true)) {
            return $country;
        }

        return match ($country) {
            '1' => 'jo',
            '2' => 'sa',
            '3' => 'eg',
            '4' => 'ps',
            default => 'jo',
        };
    }
}

