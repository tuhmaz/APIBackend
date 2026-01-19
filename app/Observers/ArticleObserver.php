<?php

namespace App\Observers;

use App\Models\Article;
use App\Services\SitemapService;

class ArticleObserver
{
    protected $sitemapService;

    public function __construct(SitemapService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    /**
     * Handle the Article "created" event.
     * Sitemap regeneration disabled for performance - use manual regeneration from dashboard
     */
    public function created(Article $article)
    {
        // Disabled: Sitemap regeneration moved to manual process via dashboard
        // $this->sitemapService->generateArticlesSitemap($article->getConnectionName());
    }

    /**
     * Handle the Article "updated" event.
     * Sitemap regeneration disabled for performance - use manual regeneration from dashboard
     */
    public function updated(Article $article)
    {
        // Disabled: Sitemap regeneration moved to manual process via dashboard
        // $this->sitemapService->generateArticlesSitemap($article->getConnectionName());
    }

    /**
     * Handle the Article "deleted" event.
     * Sitemap regeneration disabled for performance - use manual regeneration from dashboard
     */
    public function deleted(Article $article)
    {
        // Disabled: Sitemap regeneration moved to manual process via dashboard
        // $this->sitemapService->generateArticlesSitemap($article->getConnectionName());
    }
}
