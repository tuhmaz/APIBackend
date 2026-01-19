<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Services\OneSignalService;

class ArticleNotification extends Notification
{
    use Queueable;

    public $article;
    protected $oneSignalService;

    public function __construct($article)
    {
        $this->article = $article;
        $this->oneSignalService = new OneSignalService();
    }

    public function via($notifiable)
    {
        // Respect global OneSignal toggle
        if (!config('onesignal.enabled')) {
            return ['database'];
        }
        return ['database', 'onesignal'];
    }

    public function toOneSignal($notifiable)
    {
        $country = session('country', 'jordan');
        $url = route('dashboard.articles.show', ['article' => $this->article->id, 'country' => $country]);
        
        return $this->oneSignalService->sendNotification(
            'New Article Published',
            $this->article->title,
            $url,
            [
                'article_id' => $this->article->id,
                'type' => 'article'
            ]
        );
    }

    public function toArray($notifiable)
    {
        $country = session('country', 'jordan');
        // Using relative path for frontend
        $url = '/dashboard/articles/' . $this->article->id; // Assuming simple ID route for now
        
        return [
            'title' => 'مقال جديد: ' . $this->article->title,
            'message' => 'تم نشر مقال جديد في ' . $this->article->schoolClass->name ?? 'المدرسة',
            'article_id' => $this->article->id,
            'type' => 'Article',
            'url' => $url,
            'action_url' => $url,
        ];
    }
}

