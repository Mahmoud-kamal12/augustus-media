<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\FeedCache;
use App\Services\LikeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function __construct(
        private readonly LikeService $likeService,
        private readonly FeedCache $feedCache,
    ) {}

    public function store(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if ($this->likeService->like($user, $post)) {
            $this->feedCache->forgetFirstPage($user);
        }

        $likeSummary = $this->likeService->summaryForPost($post, $user);

        return ApiResponse::ok([
            'liked' => true,
            'is_liked' => $likeSummary['is_liked'],
            'likes_count' => $likeSummary['likes_count'],
        ], 'Post liked successfully.');
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if ($this->likeService->unlike($user, $post)) {
            $this->feedCache->forgetFirstPage($user);
        }

        $likeSummary = $this->likeService->summaryForPost($post, $user);

        return ApiResponse::ok([
            'liked' => false,
            'is_liked' => $likeSummary['is_liked'],
            'likes_count' => $likeSummary['likes_count'],
        ], 'Post unliked successfully.');
    }
}
