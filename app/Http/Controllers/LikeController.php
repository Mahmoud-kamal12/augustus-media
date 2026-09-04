<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\FeedCache;
use App\Services\LikeService;
use Illuminate\Http\JsonResponse;

class LikeController extends Controller
{
    public function __construct(
        private LikeService $likes,
        private FeedCache $feedCache,
    ) {}

    public function store(Post $post): JsonResponse
    {
        $user = request()->user();

        if ($this->likes->like($user, $post)) {
            $this->feedCache->forgetFirstPage($user);
        }

        return response()->json([
            'liked' => true,
            'is_liked' => true,
            'likes_count' => $this->likes->countsFor([$post->id])[$post->id],
        ]);
    }

    public function destroy(Post $post): JsonResponse
    {
        $user = request()->user();

        if ($this->likes->unlike($user, $post)) {
            $this->feedCache->forgetFirstPage($user);
        }

        return response()->json([
            'liked' => false,
            'is_liked' => false,
            'likes_count' => $this->likes->countsFor([$post->id])[$post->id],
        ]);
    }
}
