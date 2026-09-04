<?php

namespace App\Services;

use App\Models\Follow;
use App\Models\User;

class FollowService
{
    public function follow(User $follower, User $followedUser): bool
    {
        return Follow::query()->insertOrIgnore([
            'follower_id' => $follower->id,
            'followed_id' => $followedUser->id,
            'created_at' => now(),
        ]) === 1;
    }

    public function unfollow(User $follower, User $followedUser): bool
    {
        return Follow::query()
            ->where('follower_id', $follower->id)
            ->where('followed_id', $followedUser->id)
            ->delete() > 0;
    }
}
