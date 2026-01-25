<?php

namespace App\Observers;

use App\Models\Article;
use App\Jobs\GenerateSitemapJob;

class ArticleObserver
{
    /**
     * Handle the Article "created" event.
     */
    public function created(Article $article): void
    {
        // Dispatch after commit to ensure the article is visible to the job
        GenerateSitemapJob::dispatch($article->getConnectionName(), 'articles')
            ->afterCommit();
    }

    /**
     * Handle the Article "updated" event.
     */
    public function updated(Article $article): void
    {
        // Only regenerate sitemap if relevant fields changed
        if ($article->wasChanged(['title', 'status', 'slug', 'image_url', 'updated_at'])) {
            GenerateSitemapJob::dispatch($article->getConnectionName(), 'articles')
                ->afterCommit();
        }
    }

    /**
     * Handle the Article "deleted" event.
     */
    public function deleted(Article $article): void
    {
        GenerateSitemapJob::dispatch($article->getConnectionName(), 'articles')
            ->afterCommit();
    }
}
