<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class FollowService
{
    public function follow(User $follower, User $followedUser): bool
    {
        return DB::table('follows')->insertOrIgnore([
            'follower_id' => $follower->id,
            'followed_id' => $followedUser->id,
            'created_at' => now(),
        ]) === 1;
    }

    public function unfollow(User $follower, User $followedUser): bool
    {
        return DB::table('follows')
            ->where('follower_id', $follower->id)
            ->where('followed_id', $followedUser->id)
            ->delete() > 0;
    }
}
