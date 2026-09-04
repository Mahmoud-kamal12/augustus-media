<?php

namespace App\Services;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

class LikeService
{
    public function like(User $user, Post $post): bool
    {
        try {
            Like::query()->create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'created_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
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
