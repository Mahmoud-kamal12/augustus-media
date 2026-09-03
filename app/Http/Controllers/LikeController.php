<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\LikeService;
use Illuminate\Http\JsonResponse;

class LikeController extends Controller
{
    public function __construct(private LikeService $likes)
    {
    }

    public function store(Post $post): JsonResponse
    {
        $this->likes->like(request()->user(), $post);

        return response()->json([
            'liked' => true,
            'is_liked' => true,
            'likes_count' => $this->likes->countsFor([$post->id])[$post->id],
        ]);
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->likes->unlike(request()->user(), $post);

        return response()->json([
            'liked' => false,
            'is_liked' => false,
            'likes_count' => $this->likes->countsFor([$post->id])[$post->id],
        ]);
    }
}
