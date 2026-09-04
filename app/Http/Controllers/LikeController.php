<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\LikeService;
use App\Services\UserFeedCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function __construct(
        private readonly LikeService $likeService,
        private readonly UserFeedCache $userFeedCache,
    ) {}

    public function store(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if ($this->likeService->like($user, $post)) {
            $this->userFeedCache->invalidateFirstPage($user);
        }

        $post->refresh();
        $likesCount = (int) $post->likes_count;

        $likeResponseData = [
            'liked' => true,
            'is_liked' => true,
            'likes_count' => $likesCount,
        ];

        return ApiResponse::ok($likeResponseData, 'Post liked successfully.');
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if ($this->likeService->unlike($user, $post)) {
            $this->userFeedCache->invalidateFirstPage($user);
        }

        $post->refresh();
        $likesCount = (int) $post->likes_count;

        $likeResponseData = [
            'liked' => false,
            'is_liked' => false,
            'likes_count' => $likesCount,
        ];

        return ApiResponse::ok($likeResponseData, 'Post unliked successfully.');
    }
}
