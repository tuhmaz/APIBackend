<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Category;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FrontendNewsController extends Controller
{
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
      return $request->session()->get('database', 'jo');
  }

  public function index(Request $request)
{
    $database = $this->getConnection($request);

    $categories = Category::on($database)
        ->select('id', 'name', 'slug', 'parent_id')
        ->withCount('posts')
        ->orderBy('name')
        ->get();

    $query = Post::on($database)
        ->with('category')
        ->where('is_active', true); // عرض البوستات النشطة فقط

    // Total across all posts (not affected by current filters)
    $totalPosts = Post::on($database)->where('is_active', true)->count();

    // فلترة حسب الفئة (category) إذا وُجدت
    if ($request->has('category') && !empty($request->input('category'))) {
        $categorySlug = $request->input('category');
        $category = Category::on($database)->where('slug', $categorySlug)->first();
        if ($category) {
            $query->where('category_id', $category->id);
        } else {
            $query->whereNull('category_id');
        }
    }

    // فلترة حسب الكلمة الدلالية (keyword) إذا وُجدت
    if ($request->has('keyword') && !empty($request->input('keyword'))) {
        $keyword = $request->input('keyword');
        // يمكنك اختيار أي أعمدة للبحث فيها، العنوان أو الوصف
        $query->where(function($q) use ($keyword) {
            $q->where('title', 'like', '%' . $keyword . '%')
              ->orWhere('content', 'like', '%' . $keyword . '%')
              ->orWhere('meta_description', 'like', '%' . $keyword . '%');
        });
    }

    // ترتيب البوستات: الأحدث أولاً
    $posts = $query->orderBy('created_at', 'desc')->paginate(12);

    return view('content.frontend.news.index', compact('posts', 'categories', 'database', 'totalPosts'));
}



  public function show(Request $request, $database, string $id)
  {
      session(['database' => $database]);

      try {
          $news = Post::on($database)
              ->with(['category', 'attachments'])
              ->findOrFail($id);

          // جلب التعليقات مع المستخدمين وردود الأفعال
          $comments = Comment::query()
              ->where('commentable_type', Post::class)
              ->where('commentable_id', $id)
              ->with(['reactions', 'user'])
              ->get();

          $news->setRelation('comments', $comments);

          // جلب المؤلف من قاعدة البيانات الرئيسية
          $author = \App\Models\User::on('jo')->find($news->author_id);
          $news->setRelation('author', $author);

          $randomNews = Post::on($database)
              ->where('id', '!=', $id)
              ->inRandomOrder()
              ->limit(3)
              ->get();

          return view('content.frontend.news.show', compact('news', 'randomNews', 'database'));

      } catch (\Exception $e) {
          Log::error('Error in show post:', [
              'error' => $e->getMessage(),
              'database' => $database,
              'news_id' => $id
          ]);

          return redirect()->route('content.frontend.posts.index', ['database' => $database])
              ->with('error', __('Unable to load the post.'));
      }
  }

  /**
   * دالة واحدة للتعامل مع ربط الكلمات الدلالية وإرجاع النص بعد الاستبدال
   */
  private function createKeywordLinks($description, $keywords)
  {
      // إذا لا توجد كلمات دلالية أو أن النوع غير صحيح
      if (empty($keywords) || !is_string($keywords)) {
          return $description;
      }

      // تحويل السلسلة إلى مصفوفة كلمات دلالية مع إزالة الفراغات
      $keywordsArray = array_filter(array_map('trim', explode(',', $keywords)));

      // لا تفعل شيئًا إذا كانت المصفوفة فارغة
      if (empty($keywordsArray)) {
          return $description;
      }

      // قاعدة البيانات الحالية لأجل بناء الرابط
      $database = session('database', 'jo');

      // المرور على كل كلمة دلالية واستبدالها بالرابط
      foreach ($keywordsArray as $keyword) {
          // تهيئة الرابط
          $url = route('content.frontend.posts.index', [
              'database' => $database,
              'keyword'  => $keyword,
          ]);

          // مثال استخدام preg_replace_callback مع حدود كلمات \b
          // لجعل الربط حساسًا للكلمة وحدودها (وتجاهل إن كانت جزءًا من كلمة أخرى)
          $pattern = '/\b(' . preg_quote($keyword, '/') . ')\b/ui';

          $description = preg_replace_callback($pattern, function ($matches) use ($url) {
              // $matches[0] هي الكلمة المطابقة
              return '<a href="' . $url . '">' . $matches[0] . '</a>';
          }, $description);
      }

      return $description;
  }

    public function category(Request $request, string $database, $category)
    {
        // Ensure the selected database is stored in session for downstream queries
        session(['database' => $database]);
        $connection = $this->getConnection($request);

        // Resolve category by ID, then slug, then name
        $resolvedCategory = null;
        if (is_numeric($category)) {
            $resolvedCategory = Category::on($connection)->find($category);
        }
        if (!$resolvedCategory) {
            $resolvedCategory = Category::on($connection)->where('slug', $category)->first();
        }
        if (!$resolvedCategory) {
            $resolvedCategory = Category::on($connection)->where('name', $category)->first();
        }

        if (!$resolvedCategory) {
            abort(404);
        }

        $categories = Category::on($connection)->pluck('name', 'id');

        // Renamed from $news to $posts to align with posts naming
        $posts = Post::on($connection)
            ->where('category_id', $resolvedCategory->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Fetch direct subcategories of this category for hierarchical navigation
        $children = Category::on($connection)
            ->select('id', 'name', 'slug')
            ->where('parent_id', $resolvedCategory->id)
            ->orderBy('name')
            ->get();

        return view('content.frontend.news.category', [
            'posts' => $posts,
            'categories' => $categories,
            'category' => $resolvedCategory,
            'children' => $children,
            'database' => $database,
        ]);
    }

    public function filterNewsByCategory(Request $request, $database)
    {
        $connection = $this->getConnection($request);

        $categorySlug = $request->input('category');

        $category = Category::on($connection)->where('slug', $categorySlug)->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $news = Post::on($connection)
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        if ($news->isEmpty()) {
            return response()->json(['message' => 'No posts found for the selected category'], 404);
        }

        return view('content.frontend.news.partials.news-items', ['posts' => $news])->render();
    }


}
