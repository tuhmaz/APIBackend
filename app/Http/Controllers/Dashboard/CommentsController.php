<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) ($request->get('per_page', 20));
        $type = $request->get('type'); // App\Models\Article or App\Models\News
        $search = trim((string) $request->get('q', ''));

        $query = Comment::query()
            ->with(['user', 'commentable'])
            ->latest();

        if ($type === 'article') {
            $query->where('commentable_type', \App\Models\Article::class);
        } elseif ($type === 'news') {
            $query->where('commentable_type', \App\Models\News::class);
        }

        if ($search !== '') {
            $query->where('body', 'like', "%{$search}%");
        }

        $comments = $query->paginate($perPage)->withQueryString();

        return view('content.dashboard.comments.index', compact('comments', 'type', 'search'));
    }
}
