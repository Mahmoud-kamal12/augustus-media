<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Pagination\CursorPaginator;

class FeedService
{
    public function __construct(private readonly LikeService $likeService) {}

    public function followedPosts(User $user, int $perPage): CursorPaginator
    {
        $paginator = Post::query()
            ->select('posts.*')
            ->join('follows', 'follows.followed_id', '=', 'posts.user_id')
            ->where('follows.follower_id', $user->id)
            ->with('author:id,name')
            ->orderByDesc('posts.created_at')
            ->orderByDesc('posts.id')
            ->cursorPaginate($perPage);

        $posts = $paginator->getCollection();
        $postIds = $posts->modelKeys();
        $likeSummariesByPostId = $this->likeService->summariesForPosts($user, $postIds);

        $posts->each(function (Post $post) use ($likeSummariesByPostId): void {
            $likeSummary = $likeSummariesByPostId[$post->id] ?? null;
            $post->likes_count = $likeSummary['likes_count'] ?? 0;
            $post->is_liked = $likeSummary['is_liked'] ?? false;
        });

        return $paginator;
    }
}
