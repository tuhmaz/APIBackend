<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Keyword;
use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class KeywordApiController extends Controller
{
    private function getConnection(Request $request): string
    {
        return $request->query('database', session('database', 'jo'));
    }

    /**
     * GET /api/keywords
     * قائمة الكلمات المفتاحية مع عدد المقالات والبوستات
     */
    public function index(Request $request)
    {
        $database = $this->getConnection($request);

        $search = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 60);
        $type = $request->query('type', 'all');

        $perPage = max(24, min($perPage, 120));

        $articleKeywords = null;
        $postKeywords = null;

        // Articles keywords
        if (in_array($type, ['all', 'article', 'articles'])) {
            $articleKeywords = Keyword::on($database)
                ->whereHas('articles')
                ->when($search !== '', fn($q) => $q->where('keyword', 'like', "%$search%"))
                ->withCount(['articles as items_count'])
                ->orderBy('keyword')
                ->paginate($perPage)
                ->withQueryString();
        }

        // Posts keywords
        if (in_array($type, ['all', 'post', 'posts'])) {
            $postKeywords = Keyword::on($database)
                ->whereHas('posts')
                ->when($search !== '', fn($q) => $q->where('keyword', 'like', "%$search%"))
                ->withCount(['posts as items_count'])
                ->orderBy('keyword')
                ->paginate($perPage)
                ->withQueryString();
        }

        return new BaseResource([
            'database' => $database,
            'query' => $search,
            'per_page' => $perPage,
            'article_keywords' => $articleKeywords,
            'post_keywords' => $postKeywords,
        ]);
    }

    /**
     * GET /api/keywords/{keyword}
     * المقالات والبوستات المرتبطة بكلمة مفتاحية
     */
    public function show(Request $request, string $keyword)
    {
        $database = $this->getConnection($request);

        $keywordModel = Keyword::on($database)
            ->where('keyword', $keyword)
            ->firstOrFail();

        $search = trim((string)$request->query('q', ''));
        $sort   = $request->query('sort', 'latest');  // latest | title
        $perPage = (int) $request->query('per_page', 12);

        $perPage = max(6, min($perPage, 48));

        // Base queries
        $articleQuery = $keywordModel->articles();
        $postQuery = $keywordModel->posts();

        // Filtering
        if ($search !== '') {
            $articleQuery->where(function ($b) use ($search) {
                $b->where('title', 'like', "%$search%")
                  ->orWhere('content', 'like', "%$search%");
            });

            $postQuery->where(function ($b) use ($search) {
                $b->where('title', 'like', "%$search%")
                  ->orWhere('content', 'like', "%$search%")
                  ->orWhere('meta_description', 'like', "%$search%");
            });
        }

        // Sorting
        if ($sort === 'title') {
            $articleQuery->orderBy('title');
            $postQuery->orderBy('title');
        } else {
            $articleQuery->orderByDesc('id');
            $postQuery->orderByDesc('id');
        }

        // Paginate
        $articles = $articleQuery->paginate($perPage)->withQueryString();
        $posts = $postQuery->paginate($perPage)->withQueryString();

        // Select best OG image
        $ogImage = null;
        if (($a = $articles->first()) && !empty($a->image_url)) {
            $ogImage = $a->image_url;
        } elseif (($p = $posts->first()) && !empty($p->image)) {
            $ogImage = asset('storage/' . $p->image);
        }

        return new BaseResource([
            'database' => $database,
            'keyword' => $keywordModel,
            'filters' => [
                'q' => $search,
                'sort' => $sort,
                'per_page' => $perPage,
            ],
            'articles' => $articles,
            'posts' => $posts,
            'og_image' => $ogImage,
        ]);
    }
}
