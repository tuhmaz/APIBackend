<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PostShare;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\BaseResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PostShareController extends Controller
{
    private function connection(string $country): string
    {
        return match ($country) {
            '1','jordan','jo'    => 'jo',
            '2','sa','saudi'     => 'sa',
            '3','egypt','eg'     => 'eg',
            '4','palestine','ps' => 'ps',
            default => throw new NotFoundHttpException("Invalid country"),
        };
    }

    public function index(Request $request, $postId)
    {
        $country = $request->country ?? '1';
        $db = $this->connection($country);

        $shares = PostShare::on($db)
            ->where('post_id', $postId)
            ->with(['user:id,name,profile_photo_path'])
            ->latest()
            ->paginate(20);

        return new BaseResource($shares);
    }

    public function store(Request $request, $postId)
    {
        $request->validate([
            'platform' => 'required|string',
        ]);

        $country = $request->country ?? '1';
        $db = $this->connection($country);

        // Verify post exists
        $post = Post::on($db)->findOrFail($postId);

        $userId = Auth::id();

        // Check if already shared
        $share = PostShare::on($db)->firstOrCreate(
            [
                'post_id' => $postId,
                'user_id' => $userId,
                'platform' => $request->platform,
            ],
            [
                'database' => $db,
            ]
        );

        return new BaseResource([
            'message' => 'Post shared successfully',
            'share' => $share
        ]);
    }
}
