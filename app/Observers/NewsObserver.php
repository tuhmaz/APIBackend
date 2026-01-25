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
        // Dispatch after commit to ensure the news item is visible to the job
        GenerateSitemapJob::dispatch($news->getConnectionName(), 'news')
            ->afterCommit();
    }

    /**
     * Handle the News "updated" event.
     */
    public function updated(News $news): void
    {
        // Only regenerate sitemap if relevant fields changed
        if ($news->wasChanged(['title', 'is_active', 'slug', 'image', 'updated_at'])) {
            GenerateSitemapJob::dispatch($news->getConnectionName(), 'news')
                ->afterCommit();
        }
    }

    /**
     * Handle the News "deleted" event.
     */
    public function deleted(News $news): void
    {
        GenerateSitemapJob::dispatch($news->getConnectionName(), 'news')
            ->afterCommit();
    }
}
