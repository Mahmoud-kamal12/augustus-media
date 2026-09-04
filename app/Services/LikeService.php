<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LikeService
{
    public function like(User $user, Post $post): bool
    {
        return DB::table('likes')->insertOrIgnore([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]) === 1;
    }

    public function unlike(User $user, Post $post): bool
    {
        return DB::table('likes')
            ->where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->delete() > 0;
    }
}
