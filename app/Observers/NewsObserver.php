<?php

namespace App\Observers;

use App\Models\News;
use App\Services\SitemapService;

class NewsObserver
{
    protected $sitemapService;

    public function __construct(SitemapService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    /**
     * Handle the News "created" event.
     * Disabled for performance - use manual sitemap regeneration
     */
    public function created(News $news)
    {
        // Disabled for performance
    }

    /**
     * Handle the News "updated" event.
     * Disabled for performance - use manual sitemap regeneration
     */
    public function updated(News $news)
    {
        // Disabled for performance
    }

    /**
     * Handle the News "deleted" event.
     * Disabled for performance - use manual sitemap regeneration
     */
    public function deleted(News $news)
    {
        // Disabled for performance
    }
}
