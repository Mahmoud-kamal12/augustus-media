<?php

namespace App\Services;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;

class LikeService
{
    public function like(User $user, Post $post): bool
    {
        $likeKey = [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ];
        $likeData = [
            'created_at' => now(),
        ];

        $like = Like::query()->createOrFirst($likeKey, $likeData);

        return $like->wasRecentlyCreated;
    }

    public function unlike(User $user, Post $post): bool
    {
        $like = Like::query()
            ->where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $like) {
            return false;
        }

        return (bool) $like->delete();
    }
}
