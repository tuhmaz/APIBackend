<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\SchoolClass;
use App\Models\Post;
use App\Models\Category;
use App\Models\SitemapExclusion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;
use Carbon\Carbon;
use App\Http\Resources\BaseResource;

class SitemapApiController extends Controller
{
    /**
     * اختيار اتصال قاعدة البيانات
     */
    private function getConnection(string $db): string
    {
        return match ($db) {
            'sa' => 'sa',
            'eg' => 'eg',
            'ps' => 'ps',
            default => 'jo'
        };
    }

    /**
     * عرض حالة جميع ملفات السايت ماب
     */
    public function status(Request $request)
    {
        $db = $request->input('database', 'jo');
        $types = ['articles', 'post', 'static'];
        $list = [];

        foreach ($types as $type) {
            $file = "sitemaps/sitemap_{$type}_{$db}.xml";

            $list[$type] = [
                'exists' => Storage::disk('public')->exists($file),
                'last_modified' =>
                    Storage::disk('public')->exists($file)
                    ? Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file))->toDateTimeString()
                    : null,
                'url' => Storage::disk('public')->exists($file)
                    ? url("storage/{$file}")
                    : null
            ];
        }

        return new BaseResource([
            'database' => $db,
            'sitemaps' => $list
        ]);
    }

    /**
     * توليد جميع الخرائط
     */
    public function generateAll(Request $request)
    {
        try {
            $db = $request->input('database', 'jo');
            Log::info("Starting sitemap generation for database: {$db}");
            Log::info("Storage public root: " . config('filesystems.disks.public.root'));
            
            $connection = $this->getConnection($db);

            $this->generateArticles($connection, $db);
            $this->generatePosts($connection, $db);
            $this->generateStatic($connection, $db);
            $this->generateIndex($db);

            Log::info("Sitemap generation completed for database: {$db}");

            return new BaseResource([
                'database' => $db,
                'message' => 'All sitemaps have been generated'
            ]);
        } catch (\Exception $e) {
            Log::error("Sitemap generation failed: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * حذف ملف خريطة موقع
     */
    public function delete($type, $database)
    {
        $file = "sitemaps/sitemap_{$type}_{$database}.xml";

        if (!Storage::disk('public')->exists($file)) {
            return (new BaseResource(['message' => 'Sitemap not found']))
                ->response(request())
                ->setStatusCode(404);
        }

        Storage::disk('public')->delete($file);

        return new BaseResource(['message' => 'Sitemap deleted successfully']);
    }

    /**
     * ──────────────── توليد Sitemap المقالات ────────────────
     */
    private function generateArticles(string $connection, string $db)
    {
        Log::info("Generating articles sitemap for {$db} using connection {$connection}");
        $sitemap = Sitemap::create();
        $count = 0;

        Article::on($connection)->get()->each(function ($article) use ($sitemap, $db, &$count) {
            $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com') . '/' . $db . '/articles/' . $article->id;
            $url = Url::create($frontendUrl)
                ->setLastModificationDate($article->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.80);

            // الصورة
            $image = $article->image_url ?: env('APP_URL', 'https://api.alemancenter.com') . '/assets/img/front-pages/icons/articles_default_image.webp';
            $url->addImage($image, $article->alt ?? $article->title);

            $sitemap->add($url);
            $count++;
        });

        Log::info("Found {$count} articles. Writing to sitemaps/sitemap_articles_{$db}.xml");

        Storage::disk('public')
            ->put("sitemaps/sitemap_articles_{$db}.xml", $sitemap->render());
            
        Log::info("File written: sitemaps/sitemap_articles_{$db}.xml");
    }

    /**
     * ──────────────── توليد Sitemap البوستات ────────────────
     */
    private function generatePosts(string $connection, string $db)
    {
        $sitemap = Sitemap::create();

        Post::on($connection)->get()->each(function ($post) use ($sitemap, $db) {
            $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com') . '/' . $db . '/posts/' . $post->id;
            $url = Url::create($frontendUrl)
                ->setLastModificationDate($post->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.70);

            $image = $post->image
                ? env('APP_URL', 'https://api.alemancenter.com') . Storage::url($post->image)
                : env('APP_URL', 'https://api.alemancenter.com') . '/assets/img/front-pages/icons/articles_default_image.webp';

            $url->addImage($image, $post->alt ?? $post->title);

            $sitemap->add($url);
        });

        Storage::disk('public')
            ->put("sitemaps/sitemap_post_{$db}.xml", $sitemap->render());
    }

    /**
     * ──────────────── توليد Sitemap الثابت ────────────────
     */
    private function generateStatic(string $connection, string $db)
    {
        $sitemap = Sitemap::create();

        // Home
        $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com');
        $sitemap->add(
            Url::create($frontendUrl)
                ->setPriority(1.0)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
        );

        // الصفوف
        SchoolClass::on($connection)->get()->each(function ($class) use ($sitemap, $db) {
            $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com') . '/' . $db . '/classes/' . $class->id;
            $sitemap->add(
                Url::create($frontendUrl)
                    ->setLastModificationDate($class->updated_at)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.60)
            );
        });

        // التصنيفات
        Category::on($connection)->get()->each(function ($category) use ($sitemap, $db) {
            $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com') . '/' . $db . '/categories/' . $category->slug;
            $sitemap->add(
                Url::create($frontendUrl)
                    ->setLastModificationDate($category->updated_at)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.50)
            );
        });

        Storage::disk('public')->put(
            "sitemaps/sitemap_static_{$db}.xml",
            $sitemap->render()
        );
    }

    /**
     * ──────────────── توليد Sitemap Index ────────────────
     */
    private function generateIndex(string $db)
    {
        $index = SitemapIndex::create();
        $types = ['articles', 'post', 'static'];

        foreach ($types as $type) {
            $file = "sitemaps/sitemap_{$type}_{$db}.xml";

            if (Storage::disk('public')->exists($file)) {
                $index->add(
                    url("storage/{$file}"),
                    Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file))
                );
            }
        }

        Storage::disk('public')->put(
            "sitemaps/sitemap_index_{$db}.xml",
            $index->render()
        );
    }
}
