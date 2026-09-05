<?php

namespace App\Jobs;

use App\Models\Follow;
use App\Models\Post;
use App\Notifications\NewPostNotification;
use App\Services\NewPostNotificationBroadcaster;
use App\Services\NewPostNotificationStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyFollowersOfNewPost implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $postId) {}

    public function tries(): int
    {
        return config('feed.notifications.tries');
    }

    public function handle(
        NewPostNotificationStore $notificationStore,
        NewPostNotificationBroadcaster $notificationBroadcaster,
    ): void {
        $post = Post::query()
            ->with('author:id,name')
            ->find($this->postId);

        if (! $post) {
            return;
        }

        $notificationPayload = NewPostNotification::payloadForPost($post);
        $lastFollowerId = 0;

        while (true) {
            $followerIds = Follow::query()
                ->where('followed_id', $post->user_id)
                ->where('follower_id', '>', $lastFollowerId)
                ->orderBy('follower_id')
                ->limit(config('feed.notifications.chunk_size'))
                ->pluck('follower_id');

            if ($followerIds->isEmpty()) {
                return;
            }

            $notificationStore->storeForFollowers($post, $followerIds, $notificationPayload);
            $notificationBroadcaster->broadcastToFollowers($followerIds, $notificationPayload);

            $lastFollowerId = (int) $followerIds->last();
        }
    }
}
