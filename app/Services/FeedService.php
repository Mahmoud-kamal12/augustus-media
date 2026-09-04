<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Pagination\CursorPaginator;

class FeedService
{
    public function followedPosts(User $user, int $perPage): CursorPaginator
    {
        return Post::query()
            ->select('posts.*')
            ->join('follows', 'follows.followed_id', '=', 'posts.user_id')
            ->where('follows.follower_id', $user->id)
            ->with('author:id,name')
            ->withLikeSummaryFor($user->id)
            ->orderByDesc('posts.created_at')
            ->orderByDesc('posts.id')
            ->cursorPaginate($perPage);
    }
}
