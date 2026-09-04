<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Pagination\CursorPaginator;

class FeedService
{
    public function __construct(private LikeService $likes) {}

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
        $postIds = $posts->pluck('id')->all();
        $counts = $this->likes->countsFor($postIds);
        $likedIds = array_flip($this->likes->likedPostIds($user, $postIds));

        $posts->each(function (Post $post) use ($counts, $likedIds): void {
            $post->likes_count = $counts[$post->id] ?? 0;
            $post->is_liked = isset($likedIds[$post->id]);
        });

        return $paginator;
    }
}
