<?php

namespace App\Observers;

use App\Models\Post;
use App\Jobs\GenerateSitemapJob;

class PostObserver
{
    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        // Dispatch with delay to batch multiple changes
        GenerateSitemapJob::dispatch($post->getConnectionName(), 'posts')
            ->delay(now()->addMinutes(2));
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        // Only regenerate sitemap if relevant fields changed
        if ($post->wasChanged(['title', 'status', 'slug', 'image_url', 'updated_at'])) {
            GenerateSitemapJob::dispatch($post->getConnectionName(), 'posts')
                ->delay(now()->addMinutes(2));
        }
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        GenerateSitemapJob::dispatch($post->getConnectionName(), 'posts')
            ->delay(now()->addMinutes(2));
    }
}
