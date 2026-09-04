<?php

namespace App\Services;

use App\Models\Follow;
use App\Models\Post;
use App\Models\User;
use Illuminate\Pagination\CursorPaginator;

class FeedService
{
    public function followedPosts(User $user, int $perPage): CursorPaginator
    {
        $postTable = Post::TABLE;
        $followTable = Follow::TABLE;

        return Post::query()
            ->select("{$postTable}.*")
            ->join($followTable, "{$followTable}.followed_id", '=', "{$postTable}.user_id")
            ->where("{$followTable}.follower_id", $user->id)
            ->with('author:id,name')
            ->withLikeSummaryFor($user->id)
            ->orderByDesc("{$postTable}.created_at")
            ->orderByDesc("{$postTable}.id")
            ->cursorPaginate($perPage);
    }
}
