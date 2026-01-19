<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\News;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Get basic stats
        $totalArticles = Article::count();
        $totalNews = News::count();
        $totalUsers = User::count();
        // Count users online if either last_activity or last_seen within 5 minutes
        $onlineWindow = now()->subMinutes(5);
        $onlineUsersCount = User::where(function($q) use ($onlineWindow) {
                $q->where('last_activity', '>=', $onlineWindow)
                  ->orWhere('last_seen', '>=', $onlineWindow);
            })
            ->count();

        // Calculate trends
        $articlesTrend = $this->calculateTrend(Article::class);
        $newsTrend = $this->calculateTrend(News::class);
        $usersTrend = $this->calculateTrend(User::class);

        // Get content analytics data for the last 7 days
        $analyticsData = $this->getContentAnalytics(7);

        // Get online users
        $onlineUsers = User::where(function($q) use ($onlineWindow) {
                $q->where('last_activity', '>=', $onlineWindow)
                  ->orWhere('last_seen', '>=', $onlineWindow);
            })
            ->select('id', 'name', 'profile_photo_path', 'last_activity', 'last_seen')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                // Fallback to last_seen when last_activity is null
                $lastActivity = $user->last_activity ?? $user->last_seen;
                $user->status = $this->getUserStatus($lastActivity);
                return $user;
            });

        // Get recent activities
        $recentActivities = $this->getRecentActivities();

        return view('content.dashboard.home')->with([
            'totalArticles' => $totalArticles,
            'totalNews' => $totalNews,
            'totalUsers' => $totalUsers,
            'onlineUsersCount' => $onlineUsersCount,
            'articlesTrend' => $articlesTrend,
            'newsTrend' => $newsTrend,
            'usersTrend' => $usersTrend,
            'analyticsData' => $analyticsData,
            'onlineUsers' => $onlineUsers,
            'recentActivities' => $recentActivities,
            'defaultAvatar' => 'assets/img/avatars/default.png'
        ]);
    }

    public function analytics(Request $request)
    {
        $days = $request->input('days', 7);
        $analyticsData = $this->getContentAnalytics($days);
        
        return response()->json([
            'dates' => $analyticsData['dates'],
            'articles' => $analyticsData['articles'],
            'news' => $analyticsData['news'],
            'comments' => $analyticsData['comments'],
            'views' => $analyticsData['views'],
            'totalViews' => $analyticsData['views']->sum(),
            'activeAuthors' => $analyticsData['authors']->max(),
            'totalComments' => $analyticsData['comments']->sum()
        ]);
    }

    private function calculateTrend($model)
    {
        $today = now();
        $lastWeek = now()->subWeek();

        $currentCount = $model::whereBetween('created_at', [$lastWeek, $today])->count();
        $previousCount = $model::whereBetween('created_at', [$lastWeek->copy()->subWeek(), $lastWeek])->count();

        if ($previousCount == 0) {
            return ['percentage' => 100, 'trend' => 'up'];
        }

        $percentage = round((($currentCount - $previousCount) / $previousCount) * 100);
        return [
            'percentage' => abs($percentage),
            'trend' => $percentage >= 0 ? 'up' : 'down'
        ];
    }

    private function getContentAnalytics($days)
    {
        $startDate = now()->subDays($days);
        
        // Get articles data
        $articles = Article::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(visit_count) as views'),
            DB::raw('COUNT(DISTINCT author_id) as authors')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->get();

        // Get news data
        $news = News::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(views) as views'),
            DB::raw('COUNT(DISTINCT author_id) as authors')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->get();

        // Get comments data
        $comments = Comment::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->get();

        // Prepare data for chart
        $dates = collect();
        for ($i = 0; $i < $days; $i++) {
            $dates->push($startDate->copy()->addDays($i)->format('Y-m-d'));
        }

        $chartData = [
            'dates' => $dates,
            'articles' => $dates->map(function ($date) use ($articles) {
                $data = $articles->firstWhere('date', $date);
                return $data ? $data->count : 0;
            }),
            'news' => $dates->map(function ($date) use ($news) {
                $data = $news->firstWhere('date', $date);
                return $data ? $data->count : 0;
            }),
            'comments' => $dates->map(function ($date) use ($comments) {
                $data = $comments->firstWhere('date', $date);
                return $data ? $data->count : 0;
            }),
            'views' => $dates->map(function ($date) use ($articles, $news) {
                $articleViews = $articles->firstWhere('date', $date)?->views ?? 0;
                $newsViews = $news->firstWhere('date', $date)?->views ?? 0;
                return $articleViews + $newsViews;
            }),
            'authors' => $dates->map(function ($date) use ($articles, $news) {
                $articleAuthors = $articles->firstWhere('date', $date)?->authors ?? 0;
                $newsAuthors = $news->firstWhere('date', $date)?->authors ?? 0;
                return $articleAuthors + $newsAuthors;
            })
        ];

        return $chartData;
    }

    private function getUserStatus($lastActivity)
    {
        $minutes = now()->diffInMinutes($lastActivity);
        if ($minutes <= 1) {
            return 'online';
        } elseif ($minutes <= 5) {
            return 'away';
        }
        return 'offline';
    }

    private function getRecentActivities()
    {
        // Combine recent articles, news, and comments
        $activities = collect();

        // Add articles
        Article::with('author')
            ->latest()
            ->take(5)
            ->get()
            ->each(function ($article) use ($activities) {
                $database = session('database', 'jo');
                $activities->push([
                    'type' => 'article',
                    'icon' => 'bx-news',
                    'action' => __('New Article Published'),
                    'description' => $article->title,
                    'time' => $article->created_at,
                    'user' => $article->author->name,
                    'user_avatar' => $article->author->profile_photo_path,
                    'user_profile_url' => route('front.members.show', $article->author->id),
                    'properties' => [
                        'url' => route('frontend.articles.show', ['database' => $database, 'article' => $article->id])
                    ],
                ]);
            });

        // Add news
        News::with('author')
            ->latest()
            ->take(5)
            ->get()
            ->each(function ($news) use ($activities) {
                $database = session('database', 'jo');
                $activities->push([
                    'type' => 'news',
                    'icon' => 'bx-broadcast',
                    'action' => __('News Item Added'),
                    'description' => $news->title,
                    'time' => $news->created_at,
                    'user' => $news->author->name,
                    'user_avatar' => $news->author->profile_photo_path,
                    'user_profile_url' => route('front.members.show', $news->author->id),
                    'properties' => [
                        'url' => route('content.frontend.posts.show', ['database' => $database, 'id' => $news->id])
                    ],
                ]);
            });

        // Add comments
        Comment::with(['user', 'commentable'])
            ->latest()
            ->take(5)
            ->get()
            ->each(function ($comment) use ($activities) {
                $database = session('database', 'jo');
                // Build base URL to the commentable item
                $baseUrl = null;
                if ($comment->commentable_type === \App\Models\Article::class) {
                    $baseUrl = route('frontend.articles.show', ['database' => $database, 'article' => $comment->commentable_id]);
                } elseif ($comment->commentable_type === \App\Models\News::class) {
                    $baseUrl = route('content.frontend.posts.show', ['database' => $database, 'id' => $comment->commentable_id]);
                }
                $commentUrl = $baseUrl ? ($baseUrl . '#comment-' . $comment->id) : null;
                $activities->push([
                    'type' => 'comment',
                    'icon' => 'bx-comment',
                    'action' => __('New Comment'),
                    'description' => Str::limit($comment->body, 100),
                    'time' => $comment->created_at,
                    'user' => $comment->user->name,
                    'user_avatar' => $comment->user->profile_photo_path,
                    'user_profile_url' => route('front.members.show', $comment->user->id),
                    'properties' => [
                        'type' => 'comment',
                        'url' => $commentUrl,
                        'comment_id' => $comment->id,
                    ],
                ]);
            });

        return $activities->sortByDesc('time')->take(10)->values();
    }
}
