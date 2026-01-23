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

        $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com');
        foreach ($types as $type) {
            $file = "sitemaps/sitemap_{$type}_{$db}.xml";

            $list[$type] = [
                'exists' => Storage::disk('frontend_public')->exists($file),
                'last_modified' =>
                    Storage::disk('frontend_public')->exists($file)
                    ? Carbon::createFromTimestamp(Storage::disk('frontend_public')->lastModified($file))->toDateTimeString()
                    : null,
                'url' => Storage::disk('frontend_public')->exists($file)
                    ? $frontendUrl . '/' . $file
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
        // زيادة الحد الأقصى لوقت التنفيذ
        set_time_limit(300); // 5 دقائق
        ini_set('memory_limit', '512M');

        try {
            $db = $request->input('database', 'jo');
            Log::info("Starting sitemap generation for database: {$db}");

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

        if (!Storage::disk('frontend_public')->exists($file)) {
            return (new BaseResource(['message' => 'Sitemap not found']))
                ->response(request())
                ->setStatusCode(404);
        }

        Storage::disk('frontend_public')->delete($file);

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
        $frontendBaseUrl = env('FRONTEND_URL', 'https://alemancenter.com');
        $appUrl = env('APP_URL', 'https://api.alemancenter.com');
        $defaultImage = $appUrl . '/assets/img/front-pages/icons/articles_default_image.webp';

        // استخدام chunk لتجنب استهلاك الذاكرة
        Article::on($connection)
            ->select(['id', 'title', 'updated_at'])
            ->chunk(500, function ($articles) use ($sitemap, $db, &$count, $frontendBaseUrl, $defaultImage) {
                foreach ($articles as $article) {
                    $frontendUrl = $frontendBaseUrl . '/' . $db . '/lesson/articles/' . $article->id;
                    $url = Url::create($frontendUrl)
                        ->setLastModificationDate($article->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(0.80);

                    // استخدام الصورة الافتراضية للمقالات
                    $url->addImage($defaultImage, $article->title);

                    $sitemap->add($url);
                    $count++;
                }
            });

        Log::info("Found {$count} articles. Writing to sitemaps/sitemap_articles_{$db}.xml");

        Storage::disk('frontend_public')
            ->put("sitemaps/sitemap_articles_{$db}.xml", $sitemap->render());

        Log::info("File written: sitemaps/sitemap_articles_{$db}.xml");
    }

    /**
     * ──────────────── توليد Sitemap البوستات ────────────────
     */
    private function generatePosts(string $connection, string $db)
    {
        Log::info("Generating posts sitemap for {$db}");
        $sitemap = Sitemap::create();
        $count = 0;
        $frontendBaseUrl = env('FRONTEND_URL', 'https://alemancenter.com');
        $appUrl = env('APP_URL', 'https://api.alemancenter.com');
        $defaultImage = $appUrl . '/assets/img/front-pages/icons/articles_default_image.webp';

        // استخدام chunk لتجنب استهلاك الذاكرة
        Post::on($connection)
            ->select(['id', 'title', 'image', 'updated_at'])
            ->chunk(500, function ($posts) use ($sitemap, $db, &$count, $frontendBaseUrl, $appUrl, $defaultImage) {
                foreach ($posts as $post) {
                    $frontendUrl = $frontendBaseUrl . '/' . $db . '/posts/' . $post->id;
                    $url = Url::create($frontendUrl)
                        ->setLastModificationDate($post->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                        ->setPriority(0.70);

                    $image = $post->image
                        ? $appUrl . Storage::url($post->image)
                        : $defaultImage;

                    $url->addImage($image, $post->title);

                    $sitemap->add($url);
                    $count++;
                }
            });

        Log::info("Found {$count} posts. Writing to sitemaps/sitemap_post_{$db}.xml");

        Storage::disk('frontend_public')
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
            Url::create($frontendUrl . '/' . $db)
                ->setPriority(1.0)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
        );

        // الصفوف
        SchoolClass::on($connection)->get()->each(function ($class) use ($sitemap, $db) {
            $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com') . '/' . $db . '/lesson/' . $class->id;
            $sitemap->add(
                Url::create($frontendUrl)
                    ->setLastModificationDate($class->updated_at)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.60)
            );
        });

        // التصنيفات
        Category::on($connection)->get()->each(function ($category) use ($sitemap, $db) {
            $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com') . '/' . $db . '/posts/category/' . $category->id;
            $sitemap->add(
                Url::create($frontendUrl)
                    ->setLastModificationDate($category->updated_at)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.50)
            );
        });

        Storage::disk('frontend_public')->put(
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
        $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com');

        foreach ($types as $type) {
            $file = "sitemaps/sitemap_{$type}_{$db}.xml";

            if (Storage::disk('frontend_public')->exists($file)) {
                $index->add(
                    $frontendUrl . '/' . $file,
                    Carbon::createFromTimestamp(Storage::disk('frontend_public')->lastModified($file))
                );
            }
        }

        Storage::disk('frontend_public')->put(
            "sitemaps/sitemap_index_{$db}.xml",
            $index->render()
        );
    }
}
