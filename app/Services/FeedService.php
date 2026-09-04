<?php

namespace App\Services;

use App\Models\Follow;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Pagination\CursorPaginator;

class FeedService
{
    public function followedPosts(User $user, int $perPage): CursorPaginator
    {
        $postTable = Post::TABLE;
        $followTable = Follow::TABLE;

        $postQuery = Post::query()
            ->select("{$postTable}.*")
            ->join($followTable, "{$followTable}.followed_id", '=', "{$postTable}.user_id")
            ->where("{$followTable}.follower_id", $user->id)
            ->with('author:id,name')
            ->withCount('likes');

        $postPaginator = $postQuery
            ->orderByDesc("{$postTable}.created_at")
            ->orderByDesc("{$postTable}.id")
            ->cursorPaginate($perPage);

        $posts = $postPaginator->getCollection();
        $postIds = $posts->pluck('id');
        $likedPostIds = Like::query()
            ->where('user_id', $user->id)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id');

        foreach ($posts as $post) {
            $post->is_liked = $likedPostIds->contains($post->id);
        }

        return $postPaginator;
    }
}
