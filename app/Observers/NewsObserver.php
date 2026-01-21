<?php

namespace App\Observers;

use App\Models\News;
use App\Jobs\GenerateSitemapJob;

class NewsObserver
{
    /**
     * Handle the News "created" event.
     */
    public function created(News $news): void
    {
        // Dispatch with delay to batch multiple changes
        GenerateSitemapJob::dispatch($news->getConnectionName(), 'news')
            ->delay(now()->addMinutes(2));
    }

    /**
     * Handle the News "updated" event.
     */
    public function updated(News $news): void
    {
        // Only regenerate sitemap if relevant fields changed
        if ($news->wasChanged(['title', 'status', 'slug', 'image_url', 'updated_at'])) {
            GenerateSitemapJob::dispatch($news->getConnectionName(), 'news')
                ->delay(now()->addMinutes(2));
        }
    }

    /**
     * Handle the News "deleted" event.
     */
    public function deleted(News $news): void
    {
        GenerateSitemapJob::dispatch($news->getConnectionName(), 'news')
            ->delay(now()->addMinutes(2));
    }
}
