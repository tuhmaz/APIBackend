<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Article;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Throwable;
use App\Http\Resources\BaseResource;

class CommentApiController extends Controller
{
    /**
     * LIST COMMENTS (API)
     */
    public function index(Request $request, $database)
    {
        try {
            // No caching for comments list - needs to be fresh for dashboard management
            $query = Comment::on($database)
                ->withoutGlobalScope('database_scope')
                ->where('database', $database)
                ->select([
                    'id',
                    'body',
                    'user_id',
                    'commentable_id',
                    'commentable_type',
                    'database',
                    'created_at',
                    'updated_at',
                ])
                ->with([
                    'user:id,name,profile_photo_path',
                    'commentable:id,title',
                ]);

            if ($request->has('q')) {
                $query->where('body', 'like', '%' . $request->q . '%');
            }

            if ($request->has('commentable_id')) {
                $query->where('commentable_id', $request->commentable_id);
            }

            if ($request->has('commentable_type')) {
                $query->where('commentable_type', $request->commentable_type);
            }

            $comments = $query->latest()->paginate($request->per_page ?? 20);

            return new BaseResource($comments);

        } catch (Throwable $e) {
            Log::error('Comment List Error: ' . $e->getMessage());
            return (new BaseResource(['message' => 'Failed to fetch comments']))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * CREATE COMMENT (API)
     */
    public function store(Request $request, $database)
    {
        try {
            $request->validate([
                'body' => 'required|string',
                'commentable_id' => 'required|integer',
                'commentable_type' => 'required|string|in:App\Models\Post,App\Models\Article',
            ]);

            // Check for URLs/links in comment body (spam protection)
            if ($this->containsUrl($request->body)) {
                return response()->json([
                    'message' => 'غير مسموح بإضافة روابط في التعليقات',
                    'success' => false
                ], 422);
            }

            DB::connection($database)->beginTransaction();

            // Fetch post/article from correct DB
            $content = app($request->commentable_type)
                ::on($database)
                ->find($request->commentable_id);

            if (!$content) {
                return (new BaseResource(['message' => 'Content not found']))
                    ->response($request)
                    ->setStatusCode(404);
            }

            // Create comment
            $comment = new Comment();
            $comment->setConnection($database);
            $comment->fill([
                'body' => $request->body,
                'user_id' => Auth::id(),
                'commentable_id' => $request->commentable_id,
                'commentable_type' => $request->commentable_type,
                'database' => $database,
            ]);

            $comment->save();

            // Clear cache (Article only)
            if ($request->commentable_type === Article::class) {
                $articleCacheKey = sprintf('article_full_%s_%d', $database, $content->id);
                $articleRenderedKey = sprintf(
                    'article_rendered_%s_%d_%d',
                    $database,
                    $content->id,
                    $content->updated_at?->getTimestamp() ?? 0
                );
                Cache::forget($articleCacheKey);
                Cache::forget($articleRenderedKey);
            }

            // Build a comment URL for Activity Log only
            $commentUrl = url("/$database/content/{$request->commentable_id}") . "#comment-" . $comment->id;

            // Log activity
            try {
                activity()
                    ->performedOn($content)
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'type' => 'comment',
                        'url' => $commentUrl,
                        'comment_id' => $comment->id,
                        'database' => $database,
                        'commentable_type' => $request->commentable_type,
                        'commentable_id' => $request->commentable_id,
                    ])
                    ->log('commented');
            } catch (Throwable $e) {
                Log::warning('Failed activity log for comment', ['error' => $e->getMessage()]);
            }

            DB::connection($database)->commit();

            return new BaseResource([
                'message' => 'Comment added successfully',
                'comment' => $comment
            ]);

        } catch (Throwable $e) {
            DB::connection($database)->rollBack();
            Log::error('Comment Create Error: ' . $e->getMessage());

            return (new BaseResource(['message' => 'Failed to create comment']))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * DELETE COMMENT (API)
     */
    public function destroy(Request $request, $database, $id)
    {
        Log::info('Delete comment request', ['database' => $database, 'id' => $id, 'user' => Auth::id()]);

        try {
            DB::connection($database)->beginTransaction();

            $comment = Comment::on($database)
                ->withoutGlobalScope('database_scope')
                ->where('database', $database)
                ->findOrFail($id);

            Log::info('Comment found', ['comment_id' => $comment->id, 'user_id' => $comment->user_id]);

            // Permission: owner or admin
            $user = Auth::user();
            $canDelete = $user &&
                ($user->id === $comment->user_id ||
                 $user->roles->contains('name', 'Admin'));

            if (!$canDelete) {
                return (new BaseResource(['message' => 'Unauthorized to delete this comment']))
                    ->response($request)
                    ->setStatusCode(403);
            }

            // Delete reactions first
            if ($comment->reactions()->count() > 0) {
                $comment->reactions()->delete();
            }

            $commentableType = $comment->commentable_type;
            $commentableId = $comment->commentable_id;

            $deleted = $comment->delete();

            if (!$deleted) {
                throw new \Exception('Failed to delete comment');
            }

            // Clear Article cache if applicable
            if ($commentableType === Article::class) {
                $article = Article::on($database)->find($commentableId);

                $cacheKey1 = sprintf('article_full_%s_%d', $database, $commentableId);
                Cache::forget($cacheKey1);

                if ($article) {
                    $cacheKey2 = sprintf(
                        'article_rendered_%s_%d_%d',
                        $database,
                        $article->id,
                        $article->updated_at?->getTimestamp() ?? 0
                    );
                    Cache::forget($cacheKey2);
                }
            }

            DB::connection($database)->commit();

            Log::info('Comment deleted successfully', ['id' => $id]);

            return new BaseResource([
                'message' => 'Comment deleted successfully'
            ]);

        } catch (Throwable $e) {
            DB::connection($database)->rollBack();
            Log::error('Comment Delete Error: ' . $e->getMessage());

            return (new BaseResource(['message' => 'Failed to delete comment']))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * Check if text contains URLs or links
     */
    private function containsUrl(string $text): bool
    {
        // Pattern to detect URLs (http, https, www, and common TLDs)
        $patterns = [
            // Standard URLs with protocol
            '/https?:\/\/[^\s]+/i',
            // URLs starting with www
            '/www\.[^\s]+/i',
            // Common domain patterns without protocol
            '/[a-zA-Z0-9][-a-zA-Z0-9]*\.(com|net|org|io|co|me|info|biz|xyz|online|site|website|shop|store|app|dev|tech|edu|gov|mil|int|tv|cc|ws|ly|gl|gd|tk|ml|ga|cf|link|click|top|win|vip|pro|asia|eu|us|uk|de|fr|ru|cn|jp|br|in|au|ca|es|it|nl|pl|se|no|fi|dk|at|ch|be|pt|gr|cz|hu|ro|bg|sk|si|lt|lv|ee|hr|rs|ua|by|kz|ae|sa|eg|jo|ps|il|tr|ir|pk|bd|my|sg|ph|th|vn|id|kr|tw|hk|mo)[^\s]*/i',
            // IP addresses
            '/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i',
            // Shortened URLs
            '/(bit\.ly|goo\.gl|tinyurl|t\.co|ow\.ly|is\.gd|buff\.ly|adf\.ly|j\.mp|v\.gd|clck\.ru|cutt\.ly|rebrand\.ly|short\.io)[^\s]*/i',
            // HTML links
            '/<a\s+[^>]*href[^>]*>/i',
            // Markdown links
            '/\[.*?\]\(.*?\)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }
}
