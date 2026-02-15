<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ArticleNotification extends Notification
{
    use Queueable;

    public $article;

    public function __construct($article)
    {
        $this->article = $article;
    }

    public function via($notifiable)
    {
        // Keep per-user inbox notifications in database.
        // Push is sent once from the controller to avoid duplicates.
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $countryCode = $this->resolveCountryCode();
        $url = "/{$countryCode}/lesson/articles/{$this->article->id}";

        return [
            'title' => 'مقال جديد: ' . $this->article->title,
            'message' => 'تم نشر مقال جديد.',
            'article_id' => $this->article->id,
            'type' => 'article',
            'url' => $url,
            'action_url' => $url,
        ];
    }

    private function resolveCountryCode(): string
    {
        $connection = method_exists($this->article, 'getConnectionName')
            ? $this->article->getConnectionName()
            : null;

        return in_array($connection, ['jo', 'sa', 'eg', 'ps'], true) ? $connection : 'jo';
    }
}

