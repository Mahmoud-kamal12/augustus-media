<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\FeedCache;
use App\Services\LikeService;
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

        return response()->json([
            'liked' => true,
            'is_liked' => true,
            'likes_count' => $this->likeService->countFor($post),
        ]);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if ($this->likeService->unlike($user, $post)) {
            $this->feedCache->forgetFirstPage($user);
        }

        return response()->json([
            'liked' => false,
            'is_liked' => false,
            'likes_count' => $this->likeService->countFor($post),
        ]);
    }
}
