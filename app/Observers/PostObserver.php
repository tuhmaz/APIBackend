<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\SitemapService;

class PostObserver
{
    protected SitemapService $sitemapService;

    public function __construct(SitemapService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    /**
     * Handle the Post "created" event.
     * Disabled for performance - use manual sitemap regeneration
     */
    public function created(Post $post): void
    {
        // Disabled for performance
    }

    /**
     * Handle the Post "updated" event.
     * Disabled for performance - use manual sitemap regeneration
     */
    public function updated(Post $post): void
    {
        // Disabled for performance
    }

    /**
     * Handle the Post "deleted" event.
     * Disabled for performance - use manual sitemap regeneration
     */
    public function deleted(Post $post): void
    {
        // Disabled for performance
    }
}
