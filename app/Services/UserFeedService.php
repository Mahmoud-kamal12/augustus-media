<?php

namespace App\Services;

use App\Http\Resources\FeedPageResource;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;

class UserFeedService
{
    public function __construct(
        private readonly UserFeedCache $userFeedCache,
    ) {}

    public function getFeedPage(User $user, int $postsPerPage, bool $useFirstPageCache, Request $request): array
    {
        if ($useFirstPageCache) {
            $cachedFeedPage = $this->userFeedCache->getFirstPage($user);

            if ($cachedFeedPage !== null) {
                return $cachedFeedPage;
            }
        }

        $postPaginator = $this->getPostsFromFollowedUsers($user, $postsPerPage);
        $posts = $postPaginator->getCollection();

        $this->addLikeStateForViewer($posts, $user);

        $feedPageResource = new FeedPageResource($postPaginator);
        $feedPage = $feedPageResource->toArray($request);

        if ($useFirstPageCache) {
            $this->userFeedCache->storeFirstPage($user, $feedPage);
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

    private function addLikeStateForViewer(Collection $posts, User $viewer): void
    {
        if ($posts->isEmpty()) {
            return;
        }

        $likedPostIds = Like::query()
            ->where('user_id', $viewer->id)
            ->whereIn('post_id', $posts->modelKeys())
            ->pluck('post_id')
            ->all();

        $likedPostIdLookup = array_flip($likedPostIds);

        foreach ($posts as $post) {
            $post->is_liked = isset($likedPostIdLookup[$post->id]);
        }
    }
}
