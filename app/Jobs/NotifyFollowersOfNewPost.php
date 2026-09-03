<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyFollowersOfNewPost implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $postId)
    {
    }

    public function handle(): void
    {
        Post::query()->find($this->postId);
    }
}
