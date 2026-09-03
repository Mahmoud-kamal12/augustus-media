<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Cache;

class FeedService
{
    public const DEFAULT_PER_PAGE = 20;

    private const FIRST_PAGE_TTL_SECONDS = 30;

    public function __construct(private LikeService $likes)
    {
    }

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

    /**
     * @param  callable(): array  $callback
     */
    public function cachedFirstPage(User $user, callable $callback): array
    {
        return Cache::remember(
            $this->firstPageKey($user),
            self::FIRST_PAGE_TTL_SECONDS,
            $callback
        );
    }

    public function forgetFirstPage(User $user): void
    {
        Cache::forget($this->firstPageKey($user));
    }

    private function firstPageKey(User $user): string
    {
        return "feed:first-page:user:{$user->id}:v1";
    }
}
