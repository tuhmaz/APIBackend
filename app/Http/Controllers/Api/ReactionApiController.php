<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Reaction\ReactionStoreRequest;
use App\Http\Resources\BaseResource;

class ReactionApiController extends Controller
{
    /**
     * POST /api/reactions
     * إضافة أو تعديل تفاعل على تعليق
     */
    public function store(ReactionStoreRequest $request)
    {
        if (!Auth::check()) {
            return (new BaseResource(['message' => 'Unauthorized']))
                ->response($request)
                ->setStatusCode(401);
        }

        $validated = $request->validated();

        $database = session('database', 'jo');

        // existing reaction
        $existing = Reaction::where('user_id', Auth::id())
                            ->where('comment_id', $validated['comment_id'])
                            ->first();

        if ($existing) {
            $existing->update([
                'type' => $validated['type'],
                'database' => $database,
            ]);

            return new BaseResource([
                'message' => 'Reaction updated successfully.',
                'data'    => $existing,
            ]);
        }

        // create new reaction
        $reaction = Reaction::create([
            'user_id'    => Auth::id(),
            'comment_id' => $validated['comment_id'],
            'type'       => $validated['type'],
            'database'   => $database,
        ]);

        return new BaseResource([
            'message' => 'Reaction added successfully.',
            'data'    => $reaction,
        ]);
    }

    /**
     * DELETE /api/reactions/{comment_id}
     * إزالة تفاعل المستخدم على تعليق معيّن
     */
    public function destroy($commentId)
    {
        if (!Auth::check()) {
            return (new BaseResource(['message' => 'Unauthorized']))
                ->response(request())
                ->setStatusCode(401);
        }

        $reaction = Reaction::where('user_id', Auth::id())
                            ->where('comment_id', $commentId)
                            ->first();

        if (!$reaction) {
            return (new BaseResource(['message' => 'No reaction found.']))
                ->response(request())
                ->setStatusCode(404);
        }

        $reaction->delete();

        return new BaseResource([
            'message' => 'Reaction removed successfully.',
        ]);
    }

    /**
     * GET /api/reactions/{comment_id}
     * جلب تفاعل المستخدم على تعليق واحد
     */
    public function show($commentId)
    {
        if (!Auth::check()) {
            return (new BaseResource(['message' => 'Unauthorized']))
                ->response(request())
                ->setStatusCode(401);
        }

        $reaction = Reaction::where('user_id', Auth::id())
                            ->where('comment_id', $commentId)
                            ->first();

        return new BaseResource([
            'data'   => $reaction,
        ]);
    }
}
