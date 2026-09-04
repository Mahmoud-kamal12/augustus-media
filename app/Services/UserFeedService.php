<?php

namespace App\Services;

use App\Http\Resources\FeedPageResource;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Collection;

class UserFeedService
{
    public function __construct(
        private readonly UserFeedCache $userFeedCache,
    ) {}

    public function getFeedPage(User $user, int $postsPerPage, bool $isFirstPage, Request $request): array
    {
        if ($isFirstPage) {
            $cachedFeedPage = $this->userFeedCache->getFirstPage($user, $postsPerPage);

            if ($cachedFeedPage !== null) {
                return $cachedFeedPage;
            }
        }

        $postPaginator = $this->getPostsFromFollowedUsers($user, $postsPerPage);
        $posts = $postPaginator->getCollection();

        $this->markPostsLikedByUser($posts, $user);

        $feedPageResource = new FeedPageResource($postPaginator);
        $feedPage = $feedPageResource->toArray($request);

        if ($isFirstPage) {
            $this->userFeedCache->storeFirstPage($user, $postsPerPage, $feedPage);
        }

        return $feedPage;
    }

    private function getPostsFromFollowedUsers(User $user, int $postsPerPage): CursorPaginator
    {
        $followedUserIds = Follow::query()
            ->select('followed_id')
            ->where('follower_id', $user->id);

        return Post::query()
            ->whereIn('user_id', $followedUserIds)
            ->with('author:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($postsPerPage);
    }

    private function markPostsLikedByUser(Collection $posts, User $user): void
    {
        if ($posts->isEmpty()) {
            return;
        }

        $postIds = $posts->pluck('id');
        $likedPostIds = Like::query()
            ->where('user_id', $user->id)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->all();

        $likedPostIdsById = array_flip($likedPostIds);

        foreach ($posts as $post) {
            $post->is_liked = isset($likedPostIdsById[$post->id]);
        }
    }
}
