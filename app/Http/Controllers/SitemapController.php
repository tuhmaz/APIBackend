<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\SchoolClass;
use App\Models\Post;
use App\Models\SitemapExclusion;
use App\Models\Category;
use Spatie\Sitemap\SitemapGenerator;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class SitemapController extends Controller
{
  private function ensureSitemapDirectory(): void
  {
    Storage::disk('frontend_public')->makeDirectory('storage/sitemaps');
  }
  public function setDatabase(Request $request)
  {
    $request->validate([
      'database' => 'required|string|in:jo,sa,eg,ps'
    ]);


    $request->session()->put('database', $request->input('database'));

    return redirect()->back();
  }

  private function getConnection(Request $request)
  {
    if ($request->has('database')) {
      $request->session()->put('database', $request->input('database'));
    }
    return $request->session()->get('database', 'jo'); // قاعدة البيانات الافتراضية
  }


  public function index(Request $request)
  {
    $database = $this->getConnection($request);
    $sitemapTypes = ['articles', 'post', 'static'];
    $sitemapData = [];

    foreach ($sitemapTypes as $type) {
      $filename = "storage/sitemaps/sitemap_{$type}_{$database}.xml";
      if (Storage::disk('frontend_public')->exists($filename)) {
        $sitemapData[$type] = [
          'exists' => true,
          'last_modified' => Storage::disk('frontend_public')->lastModified($filename)
        ];
      } else {
        $sitemapData[$type] = [
          'exists' => false,
          'last_modified' => null
        ];
      }
    }

    return view('content.dashboard.sitemap.index', compact('sitemapData', 'database'));
  }

  public function manageIndex(Request $request)
{
    if ($request->has('database')) {
        $request->session()->put('database', $request->input('database'));
    }

    $database = session('database', 'defaultDatabase'); // Assume 'defaultDatabase' as your fallback
    Config::set('database.default', $database); // Set the Laravel default database dynamically

    // Optionally, set connection for each model if they differ
    $articles = Article::on($database)->orderBy('updated_at', 'desc')->get();
    $classes = SchoolClass::on($database)->orderBy('updated_at', 'desc')->get();
    $categories = Category::on($database)->withCount('posts')->orderBy('name')->get();

    // Assuming SitemapExclusion also needs database connection context
    $statuses = SitemapExclusion::on($database)->pluck('resource_id', 'resource_type')->map(function ($id, $type) {
        return "{$type}-{$id}";
    })->flip()->map(function () {
        return true;
    })->all();

    $dbNames = [
        'jo' => 'الأردن',
        'sa' => 'السعودية',
        'eg' => 'مصر',
        'ps' => 'فلسطين'
    ];

    return view('content.dashboard.sitemap.manage', compact('articles', 'classes', 'categories', 'statuses', 'database', 'dbNames'));
}

  public function updateResourceInclusion(Request $request)
  {
    $database = $this->getConnection($request);
    $connection = $database;

    $currentExclusions = SitemapExclusion::on($connection)->get();

    // Loop through articles and update status
    foreach (Article::on($connection)->get() as $article) {
      $key = 'article_' . $article->id;
      if ($request->has($key)) {
        if (!$currentExclusions->contains('resource_type', 'article') || !$currentExclusions->contains('resource_id', $article->id)) {
          SitemapExclusion::on($connection)->create([
            'resource_type' => 'article',
            'resource_id' => $article->id,
            'is_included' => true,
          ]);
        }
      } else {
        SitemapExclusion::on($connection)
          ->where('resource_type', 'article')
          ->where('resource_id', $article->id)
          ->delete();
      }
    }

    // Loop through classes and update status
    foreach (SchoolClass::on($connection)->get() as $class) {
      $key = 'class_' . $class->id;
      if ($request->has($key)) {
        if (!$currentExclusions->contains('resource_type', 'class') || !$currentExclusions->contains('resource_id', $class->id)) {
          SitemapExclusion::on($connection)->create([
            'resource_type' => 'class',
            'resource_id' => $class->id,
            'is_included' => true,
          ]);
        }
      } else {
        SitemapExclusion::on($connection)
          ->where('resource_type', 'class')
          ->where('resource_id', $class->id)
          ->delete();
      }
    }

    // Loop through posts and update status
    foreach (Post::on($connection)->get() as $post) {
      $key = 'post_' . $post->id;
      if ($request->has($key)) {
        if (!$currentExclusions->contains('resource_type', 'post') || !$currentExclusions->contains('resource_id', $post->id)) {
          SitemapExclusion::on($connection)->create([
            'resource_type' => 'post',
            'resource_id' => $post->id,
            'is_included' => true,
          ]);
        }
      } else {
        SitemapExclusion::on($connection)
          ->where('resource_type', 'post')
          ->where('resource_id', $post->id)
          ->delete();
      }
    }

    // Loop through categories and update status
    foreach (Category::on($connection)->get() as $category) {
      $key = 'category_' . $category->id;
      if ($request->has($key)) {
        if (!$currentExclusions->contains('resource_type', 'category') || !$currentExclusions->contains('resource_id', $category->id)) {
          SitemapExclusion::on($connection)->create([
            'resource_type' => 'category',
            'resource_id' => $category->id,
            'is_included' => true,
          ]);
        }
      } else {
        SitemapExclusion::on($connection)
          ->where('resource_type', 'category')
          ->where('resource_id', $category->id)
          ->delete();
      }
    }

    return redirect()->back()->with('success', 'تم تحديث حالة الأرشفة بنجاح.');
  }

  public function generate(Request $request)
  {
    $database = $this->getConnection($request);

    // Generate sitemaps for the selected database
    $this->generateStaticSitemap($request);
    $this->generateArticlesSitemap($request);
    $this->generatePostSitemap($request);

    // Generate sitemap index
    $this->generateSitemapIndex($database);

    return redirect()->route('dashboard.sitemap.index')->with('success', 'تم توليد جميع الخرائط بنجاح.');
  }


  private function getFirstImageFromContent($content, $defaultImageUrl)
  {
    preg_match('/<img[^>]+src="([^">]+)"/', $content, $matches);
    return $matches[1] ?? $defaultImageUrl;
  }

  public function generateArticlesSitemap(Request $request)
  {
    $database = $this->getConnection($request);
    $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com');

    // إنشاء خريطة موقع جديدة
    $sitemap = new \Spatie\Sitemap\Sitemap();

    Article::on($database)->get()->each(function (Article $article) use ($sitemap, $database, $frontendUrl) {
      // إنشاء عنوان URL مع تعيين جميع السمات بشكل صريح
      $url = Url::create($frontendUrl . '/' . $database . '/lesson/articles/' . $article->id);

      // تعيين تاريخ آخر تعديل
      $url->setLastModificationDate($article->updated_at);

      // تعيين تكرار التغيير بشكل صريح
      $url->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY);

      // تعيين الأولوية بشكل صريح
      $url->setPriority(0.8);

      // تحسين معالجة الصور
      if ($article->image_url) {
        $imageUrl = $article->image_url;
      } elseif (strpos($article->content, '<img') !== false) {
        $defaultImageUrl = asset('assets/img/front-pages/icons/articles_default_image.webp');
        $imageUrl = $this->getFirstImageFromContent($article->content, $defaultImageUrl);
      } else {
        $imageUrl = asset('assets/img/front-pages/icons/articles_default_image.webp');
      }

      $altText = $article->alt ?? $article->title;

      if ($imageUrl) {
        $url->addImage($imageUrl, $altText);
      }

      $sitemap->add($url);
    });

    // Save the sitemap to the public disk
    $fileName = "storage/sitemaps/sitemap_articles_{$database}.xml";
    $this->ensureSitemapDirectory();
    Storage::disk('frontend_public')->put($fileName, $sitemap->render());

    // عودة قيمة لتأكيد نجاح العملية
    return true;
  }

  public function generatePostSitemap(Request $request)
  {
      $database = $this->getConnection($request);
      $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com');

      // Use dynamic database connection
      $posts = Post::on($database)->get();

      // إنشاء خريطة موقع مع تمكين وسوم changefreq و priority
      $sitemap = new \Spatie\Sitemap\Sitemap();

      // Note: No need to set max tags as it's handled automatically

      foreach ($posts as $post) {
          // إنشاء عنوان URL مع تعيين جميع السمات بشكل صريح
          $url = Url::create($frontendUrl . '/' . $database . '/posts/' . $post->id);

          // تعيين تاريخ آخر تعديل
          $url->setLastModificationDate($post->updated_at);

          // تعيين تكرار التغيير بشكل صريح
          $url->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY);

          // تعيين الأولوية بشكل صريح
          $url->setPriority(0.7);

          // Check if the image exists, use Storage::url to generate the correct path
          if ($post->image) {
            // Use Storage::url to generate the URL directly from the stored path
            $imagePath = Storage::url($post->image);
        } else {
            // Use the default image if no image is uploaded
            $imagePath = asset('assets/img/front-pages/icons/articles_default_image.webp');
        }


          // The alt text is based on the title or custom alt
          $altText = $post->alt ?? $post->title;

          // Add the image to the sitemap if it exists
          if ($imagePath) {
              $url->addImage($imagePath, $altText);
          }

          $sitemap->add($url);
      }

      // Save the sitemap to the public disk
      $fileName = "storage/sitemaps/sitemap_post_{$database}.xml";
      $this->ensureSitemapDirectory();
      Storage::disk('frontend_public')->put($fileName, $sitemap->render());

      // عودة قيمة لتأكيد نجاح العملية
      return true;
  }

  public function generateStaticSitemap(Request $request)
  {
    $database = $this->getConnection($request);
    $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com');
    $sitemap = Sitemap::create();

    $sitemap->add(Url::create($frontendUrl . '/' . $database)
      ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
      ->setPriority(1.0));

    SchoolClass::on($database)->get()->each(function (SchoolClass $class) use ($sitemap, $database, $frontendUrl) {
      $url = Url::create($frontendUrl . '/' . $database . '/lesson/' . $class->id)
        ->setLastModificationDate($class->updated_at)
        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
        ->setPriority(0.6);

      $sitemap->add($url);
    });

    Category::on($database)->get()->each(function (Category $category) use ($sitemap, $database, $frontendUrl) {
      $url = Url::create($frontendUrl . '/' . $database . '/posts/category/' . $category->id)
        ->setLastModificationDate($category->updated_at)
        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
        ->setPriority(0.5);

      $sitemap->add($url);
    });

     $fileName = "storage/sitemaps/sitemap_static_{$database}.xml";
    $this->ensureSitemapDirectory();
    Storage::disk('frontend_public')->put($fileName, $sitemap->render());
  }

  public function delete($type, $database)
  {
    $fileName = "storage/sitemaps/sitemap_{$type}_{$database}.xml";

    if (Storage::disk('frontend_public')->exists($fileName)) {
      Storage::disk('frontend_public')->delete($fileName);
      return redirect()->back()->with('success', 'Sitemap deleted successfully.');
    }

    return redirect()->back()->with('error', 'Sitemap not found.');
  }

  /**
   * Generate a sitemap index file that references all other sitemaps
   */
  private function generateSitemapIndex($database)
  {
    // استخدام الفئة الصحيحة لإنشاء فهرس خريطة الموقع
    $sitemapIndex = \Spatie\Sitemap\SitemapIndex::create();

    // Use a secure base URL for links in the sitemap index
    $frontendUrl = env('FRONTEND_URL', 'https://alemancenter.com');

    $types = ['articles', 'post', 'static'];

    foreach ($types as $type) {
      $fileName = "storage/sitemaps/sitemap_{$type}_{$database}.xml";
      if (Storage::disk('frontend_public')->exists($fileName)) {
        $lastModified = Storage::disk('frontend_public')->lastModified($fileName);
        $sitemapUrl = $frontendUrl . '/' . $fileName;
        $sitemapIndex->add($sitemapUrl, Carbon::createFromTimestamp($lastModified));
      }
    }

    $indexFileName = "storage/sitemaps/sitemap_index_{$database}.xml";
    $this->ensureSitemapDirectory();
    Storage::disk('frontend_public')->put($indexFileName, $sitemapIndex->render());

    return true;
  }
}
