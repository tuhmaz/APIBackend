<?php

namespace App\Services;

use App\Models\Article;
use App\Models\News;
use App\Models\Post;
use Carbon\Carbon;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL as URLFacade;

class SitemapService
{
    private function ensureSitemapDirectory(): void
    {
        Storage::disk('frontend_public')->makeDirectory('storage/sitemaps');
    }
     private function getFirstImageFromContent($content, $defaultImageUrl)
    {
        preg_match('/<img[^>]+src="([^">]+)"/', $content, $matches);
        return $matches[1] ?? $defaultImageUrl;
    }

    public function generateArticlesSitemap(string $database)
    {
        $sitemap = Sitemap::create();
        $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com');
        $apiUrl = env('APP_URL', 'https://api.alemancenter.com');
        $defaultImageUrl = $apiUrl . '/assets/img/front-pages/icons/articles_default_image.webp';

        $articles = Article::on($database)->where('status', 1)->get();

        foreach ($articles as $article) {
            $articleUrl = $frontendUrl . '/' . $database . '/lesson/articles/' . $article->id;
            $url = Url::create($articleUrl)
                ->setLastModificationDate($article->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.8);

            $imageUrl = $article->image_url ?? $this->getFirstImageFromContent($article->content, $defaultImageUrl);
            $altText = $article->alt ?? $article->title;

            if ($imageUrl) {
                $url->addImage($imageUrl, $altText);
            }

            $sitemap->add($url);
        }

        $fileName = "storage/sitemaps/sitemap_articles_{$database}.xml";
        $this->ensureSitemapDirectory();
        Storage::disk('frontend_public')->put($fileName, $sitemap->render());

        $this->updateSitemapIndex($database);
    }

    public function generateNewsSitemap(string $database)
    {
        $sitemap = Sitemap::create();
        $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com');
        $apiUrl = env('APP_URL', 'https://api.alemancenter.com');
        $defaultImageUrl = $apiUrl . '/assets/img/front-pages/icons/news_default_image.webp';

        // Fetch news items based on the selected database
        $newsItems = News::on($database)->get();

        foreach ($newsItems as $news) {
            // Create the URL for the news item
            $newsUrl = $frontendUrl . '/' . $database . '/posts/' . $news->id;
            $url = Url::create($newsUrl)
                ->setLastModificationDate($news->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.8);

            // Add image to sitemap if available
            $imageUrl = $news->image_url ?? $this->getFirstImageFromContent($news->content, $defaultImageUrl);
            $altText = $news->alt ?? $news->title;

            if ($imageUrl) {
                $url->addImage($imageUrl, $altText);
            }

            $sitemap->add($url);
        }

        // Save the sitemap
        $fileName = "storage/sitemaps/sitemap_news_{$database}.xml";
        $this->ensureSitemapDirectory();
        Storage::disk('frontend_public')->put($fileName, $sitemap->render());

        $this->updateSitemapIndex($database);
    }

    public function generatePostsSitemap(string $database)
    {
        $sitemap = Sitemap::create();
        $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com');
        $apiUrl = env('APP_URL', 'https://api.alemancenter.com');
        $defaultImageUrl = $apiUrl . '/assets/img/front-pages/icons/news_default_image.webp';

        // Fetch posts items based on the selected database
        $posts = Post::on($database)->get();

        foreach ($posts as $post) {
            // Create the URL for the post item
            $postUrl = $frontendUrl . '/' . $database . '/posts/' . $post->id;
            $url = Url::create($postUrl)
                ->setLastModificationDate($post->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.8);

            $imageUrl = null;

            if (! empty($post->image)) {
                $imageUrl = Storage::url($post->image);
            }

            if (! $imageUrl) {
                $imageUrl = $this->getFirstImageFromContent((string) $post->content, $defaultImageUrl);
            }

            $altText = $post->alt ?? $post->title;

            if ($imageUrl) {
                $url->addImage($imageUrl, $altText);
            }

            $sitemap->add($url);
        }

        // Save the sitemap
        // Remove legacy filename if it exists to avoid stale files
        $legacyFile = "storage/sitemaps/sitemap_posts_{$database}.xml";
        $this->ensureSitemapDirectory();
        if (Storage::disk('frontend_public')->exists($legacyFile)) {
            Storage::disk('frontend_public')->delete($legacyFile);
        }

        $fileName = "storage/sitemaps/sitemap_post_{$database}.xml";
        Storage::disk('frontend_public')->put($fileName, $sitemap->render());

        $this->updateSitemapIndex($database);
    }


    public function generateSitemap()
    {
        Sitemap::create()
            ->add(Url::create('/')->setPriority(1.0)->setChangeFrequency('daily'))
            ->add(Url::create('/about')->setPriority(0.8))
            ->writeToFile(public_path('sitemap.xml'));

        return response()->json(['success' => true, 'message' => 'Sitemap generated successfully']);
    }

    protected function updateSitemapIndex(string $database): void
    {
        $sitemapIndex = SitemapIndex::create();
        $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com');

        $types = ['articles', 'post', 'news', 'static'];

        foreach ($types as $type) {
            $fileName = "storage/sitemaps/sitemap_{$type}_{$database}.xml";

            if (! Storage::disk('frontend_public')->exists($fileName)) {
                continue;
            }

            $lastModified = Storage::disk('frontend_public')->lastModified($fileName);

            $sitemapIndex->add(
                $frontendUrl . '/' . $fileName,
                Carbon::createFromTimestamp($lastModified)
            );
        }

        $this->ensureSitemapDirectory();
        Storage::disk('frontend_public')->put(
            "storage/sitemaps/sitemap_index_{$database}.xml",
            $sitemapIndex->render()
        );
    }
}
