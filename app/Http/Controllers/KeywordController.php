<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use Illuminate\Http\Request;

class KeywordController extends Controller
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

    public function index(Request $request,)
    {
        $database = $request->session()->get('database', 'jo');

        $q = trim((string) $request->get('q', ''));
        $perPage = (int) $request->get('per_page', 60); // show many keywords per page
        if ($perPage < 24) { $perPage = 24; }
        if ($perPage > 120) { $perPage = 120; }

        $articleKeywords = Keyword::on($database)
            ->whereHas('articles')
            ->when($q !== '', fn($b) => $b->where('keyword', 'like', "%$q%"))
            ->withCount(['articles as items_count'])
            ->orderBy('keyword')
            ->paginate($perPage)
            ->withQueryString();

        $postKeywords = Keyword::on($database)
            ->whereHas('posts')
            ->when($q !== '', fn($b) => $b->where('keyword', 'like', "%$q%"))
            ->withCount(['posts as items_count'])
            ->orderBy('keyword')
            ->paginate($perPage)
            ->withQueryString();

        return view('content.frontend.keywords.index', compact('articleKeywords', 'postKeywords', 'database', 'q', 'perPage'));
    }

    public function indexByKeyword(Request $request, $database, $keyword)
    {
        // Use selected database from session
        $database = $request->session()->get('database', 'jo');

        $keywordModel = Keyword::on($database)->where('keyword', $keyword)->firstOrFail();

        // Query params
        $q = trim((string) $request->get('q', ''));
        $sort = $request->get('sort', 'latest'); // latest | title
        $perPage = (int) $request->get('per_page', 12);
        if ($perPage < 6) { $perPage = 6; }
        if ($perPage > 48) { $perPage = 48; }

        // Build base queries (relations inherit the model's connection)
        $articleQuery = $keywordModel->articles();
        $postQuery = $keywordModel->posts();

        // Optional search
        if ($q !== '') {
            $articleQuery->where(function ($builder) use ($q) {
                $builder->where('title', 'like', "%$q%")
                    ->orWhere('content', 'like', "%$q%");
            });
            $postQuery->where(function ($builder) use ($q) {
                $builder->where('title', 'like', "%$q%")
                    ->orWhere('content', 'like', "%$q%")
                    ->orWhere('meta_description', 'like', "%$q%");
            });
        }

        // Sorting (safe columns: id, title)
        if ($sort === 'title') {
            $articleQuery->orderBy('title');
            $postQuery->orderBy('title');
        } else {
            // default latest by id desc
            $articleQuery->orderByDesc('id');
            $postQuery->orderByDesc('id');
        }

        // Paginate and keep query string
        $articles = $articleQuery->paginate($perPage)->withQueryString();
        $posts = $postQuery->paginate($perPage)->withQueryString();

        // Determine a safe image for social meta from first page of articles or posts
        $firstArticle = $articles->first();
        $firstPost = $posts->first();
        $ogImage = null;
        if ($firstArticle && !empty($firstArticle->image_url)) {
            $ogImage = $firstArticle->image_url;
        } elseif ($firstPost && !empty($firstPost->image)) {
            $ogImage = asset('storage/' . $firstPost->image);
        }

        return view('content.frontend.keywords.keyword', [
            'articles' => $articles,
            'posts' => $posts,
            'keyword' => $keywordModel,
            'ogImage' => $ogImage,
        ]);
    }
}
