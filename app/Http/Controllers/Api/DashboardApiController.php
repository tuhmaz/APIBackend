<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\News;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class DashboardApiController extends Controller
{
    /**
     * GET /api/dashboard
     * لوحة التحكم الرئيسية (إحصائيات عامة)
     */
    public function index()
    {
        // Cache totals for 60 seconds
        $totals = Cache::remember('dashboard_totals', 60, function () {
            return [
                'articles' => Article::count(),
                'news'     => News::count(),
                'users'    => User::count(),
            ];
        });

        // Online users - cache for 30 seconds
        $onlineData = Cache::remember('dashboard_online_users', 30, function () {
            $onlineWindow = now()->subMinutes(5);

            $count = User::where(function($q) use ($onlineWindow) {
                $q->where('last_activity', '>=', $onlineWindow)
                  ->orWhere('last_seen', '>=', $onlineWindow);
            })->count();

            $users = User::where(function($q) use ($onlineWindow) {
                    $q->where('last_activity', '>=', $onlineWindow)
                      ->orWhere('last_seen', '>=', $onlineWindow);
                })
                ->select('id','name','profile_photo_path','last_activity','last_seen')
                ->limit(5)
                ->get();

            return ['count' => $count, 'users' => $users];
        });

        // Process online users status
        $onlineUsers = $onlineData['users']->map(function ($user) {
            $lastActivity = $user->last_activity ?? $user->last_seen;
            $user->status = $this->getUserStatus($lastActivity);
            return $user;
        });

        // Cache trends for 5 minutes (they don't change often)
        $trends = Cache::remember('dashboard_trends', 300, function () {
            return [
                'articles' => $this->calculateTrend(Article::class),
                'news'     => $this->calculateTrend(News::class),
                'users'    => $this->calculateTrend(User::class),
            ];
        });

        // Cache analytics for 2 minutes
        $analyticsData = Cache::remember('dashboard_analytics_7', 120, function () {
            return $this->getContentAnalytics(7);
        });

        // Cache recent activities for 30 seconds
        $recentActivities = Cache::remember('dashboard_recent_activities', 30, function () {
            return $this->getRecentActivities();
        });

        return new BaseResource([
            'totals' => array_merge($totals, ['online_users' => $onlineData['count']]),
            'trends' => $trends,
            'analytics' => $analyticsData,
            'onlineUsers' => $onlineUsers,
            'recentActivities' => $recentActivities,
        ]);
    }

    /**
     * GET /api/dashboard/analytics?days=7
     */
    public function analytics(Request $request)
    {
        $days = min((int) $request->input('days', 7), 30); // Max 30 days

        // Cache analytics based on days parameter
        $analyticsData = Cache::remember("dashboard_analytics_{$days}", 120, function () use ($days) {
            return $this->getContentAnalytics($days);
        });

        return new BaseResource($analyticsData);
    }

    private function calculateTrend($model)
    {
        $today = now();
        $lastWeek = now()->subWeek();

        $currentCount  = $model::whereBetween('created_at', [$lastWeek, $today])->count();
        $previousCount = $model::whereBetween('created_at', [$lastWeek->copy()->subWeek(), $lastWeek])->count();

        if ($previousCount == 0) {
            return ['percentage' => 100, 'trend' => 'up'];
        }

        $percentage = round((($currentCount - $previousCount) / $previousCount) * 100);

        return [
            'percentage' => abs($percentage),
            'trend'      => $percentage >= 0 ? 'up' : 'down'
        ];
    }

    private function getContentAnalytics($days)
    {
        $startDate = now()->subDays($days);

        // Articles aggregated
        $articles = Article::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(visit_count) as views'),
            DB::raw('COUNT(DISTINCT author_id) as authors')
        )
        ->where('created_at', '>=', $startDate)
        ->groupBy('date')
        ->get();

        // News aggregated
        $news = News::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(views) as views'),
            DB::raw('COUNT(DISTINCT author_id) as authors')
        )
        ->where('created_at', '>=', $startDate)
        ->groupBy('date')
        ->get();

        // Comments aggregated
        $comments = Comment::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
        ->where('created_at', '>=', $startDate)
        ->groupBy('date')
        ->get();

        // Generate date list
        $dates = collect();
        for ($i = 0; $i < $days; $i++) {
            $dates->push($startDate->copy()->addDays($i)->format('Y-m-d'));
        }

        return [
            'dates' => $dates,
            'articles' => $dates->map(function ($date) use ($articles) {
                return optional($articles->firstWhere('date',$date))->count ?? 0;
            }),
            'news' => $dates->map(function ($date) use ($news) {
                return optional($news->firstWhere('date',$date))->count ?? 0;
            }),
            'comments' => $dates->map(function ($date) use ($comments) {
                return optional($comments->firstWhere('date',$date))->count ?? 0;
            }),
            'views' => $dates->map(function ($date) use ($articles, $news) {
                return
                    (optional($articles->firstWhere('date',$date))->views ?? 0)
                    +
                    (optional($news->firstWhere('date',$date))->views ?? 0);
            }),
            'authors' => $dates->map(function ($date) use ($articles,$news) {
                return
                    (optional($articles->firstWhere('date',$date))->authors ?? 0)
                    +
                    (optional($news->firstWhere('date',$date))->authors ?? 0);
            }),
        ];
    }

    private function getUserStatus($lastActivity)
    {
        $minutes = now()->diffInMinutes($lastActivity);

        if ($minutes <= 1) return 'online';
        if ($minutes <= 5) return 'away';
        return 'offline';
    }

    private function getRecentActivities()
    {
        $activities = collect();
        $database = session('database','jo');

        // Articles
        Article::with('author')
            ->latest()
            ->take(5)
            ->get()
            ->each(function($article) use ($activities,$database){
                $activities->push([
                    'type' => 'article',
                    'title' => $article->title,
                    'created_at' => $article->created_at,
                    'author' => [
                        'name' => $article->author->name ?? 'مجهول',
                        'avatar' => $article->author->profile_photo_path ?? null
                    ],
                    'url' => env('FRONTEND_URL', 'https://alemancenter.com') . '/' . $database . '/articles/' . $article->id
                ]);
            });

        // News
        News::with('author')
            ->latest()
            ->take(5)
            ->get()
            ->each(function($item) use ($activities,$database){
                $activities->push([
                    'type' => 'news',
                    'title' => $item->title,
                    'created_at' => $item->created_at,
                    'author' => [
                        'name' => $item->author->name ?? 'مجهول',
                        'avatar' => $item->author->profile_photo_path ?? null
                    ],
                    'url' => env('FRONTEND_URL', 'https://alemancenter.com') . '/' . $database . '/posts/' . $item->id
                ]);
            });

        // Comments
        Comment::with(['user','commentable'])
            ->latest()
            ->take(5)
            ->get()
            ->each(function($comment) use ($activities,$database){
                // Skip if user is null
                if (!$comment->user) return;

                $url = null;
                if ($comment->commentable_type === Article::class) {
                    $url = env('FRONTEND_URL', 'https://alemancenter.com') . '/' . $database . '/articles/' . $comment->commentable_id;
                }
                elseif ($comment->commentable_type === News::class) {
                    $url = env('FRONTEND_URL', 'https://alemancenter.com') . '/' . $database . '/posts/' . $comment->commentable_id;
                }

                $activities->push([
                    'type' => 'comment',
                    'body' => Str::limit($comment->body,100),
                    'created_at' => $comment->created_at,
                    'user' => [
                        'name' => $comment->user->name ?? 'مجهول',
                        'avatar' => $comment->user->profile_photo_path ?? null
                    ],
                    'url' => $url ? $url . '#comment-'.$comment->id : null
                ]);
            });

        return $activities->sortByDesc('created_at')->take(10)->values();
    }
}
