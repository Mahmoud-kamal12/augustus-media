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
        $likeCountsByPostId = $this->likeService->countsFor($postIds);
        $likedPostIds = array_flip($this->likeService->likedPostIds($user, $postIds));

        $posts->each(function (Post $post) use ($likeCountsByPostId, $likedPostIds): void {
            $post->likes_count = $likeCountsByPostId[$post->id] ?? 0;
            $post->is_liked = isset($likedPostIds[$post->id]);
        });

        return $paginator;
    }
}
