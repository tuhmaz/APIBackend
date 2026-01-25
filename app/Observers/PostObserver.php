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
        // Dispatch after commit to ensure the post is visible to the job
        GenerateSitemapJob::dispatch($post->getConnectionName(), 'posts')
            ->afterCommit();
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        // Only regenerate sitemap if relevant fields changed
        if ($post->wasChanged(['title', 'is_active', 'slug', 'image', 'updated_at'])) {
            GenerateSitemapJob::dispatch($post->getConnectionName(), 'posts')
                ->afterCommit();
        }
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        GenerateSitemapJob::dispatch($post->getConnectionName(), 'posts')
            ->afterCommit();
    }
}
