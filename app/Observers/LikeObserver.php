<?php

namespace App\Observers;

use App\Models\Like;
use App\Models\Post;

class LikeObserver
{
    public function created(Like $like): void
    {
        Post::query()
            ->whereKey($like->post_id)
            ->increment('likes_count');
    }

    public function deleted(Like $like): void
    {
        Post::query()
            ->whereKey($like->post_id)
            ->where('likes_count', '>', 0)
            ->decrement('likes_count');
    }
}
