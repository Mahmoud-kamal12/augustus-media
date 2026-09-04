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

        return ApiResponse::ok([
            'liked' => true,
            'is_liked' => true,
            'likes_count' => $this->likeService->countFor($post),
        ], 'Post liked successfully.');
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if ($this->likeService->unlike($user, $post)) {
            $this->feedCache->forgetFirstPage($user);
        }

        return ApiResponse::ok([
            'liked' => false,
            'is_liked' => false,
            'likes_count' => $this->likeService->countFor($post),
        ], 'Post unliked successfully.');
    }
}
