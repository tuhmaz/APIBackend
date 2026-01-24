<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TogetherAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiContentController extends Controller
{
    protected $aiService;

    public function __construct(TogetherAiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate content based on title
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate(Request $request)
    {
        if (!$request->user()->can('manage articles') && !$request->user()->can('manage posts')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|min:3|max:255',
        ]);

        try {
            $content = $this->aiService->generateArticleContent($request->title);

            return response()->json([
                'success' => true,
                'content' => $content
            ]);

        } catch (\Exception $e) {
            Log::error('AI Controller Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate content. Please try again later.'
            ], 500);
        }
    }
}
