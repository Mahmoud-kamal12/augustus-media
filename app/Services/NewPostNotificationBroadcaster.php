<?php

namespace App\Services;

use App\Events\NewPostNotificationBroadcasted;
use Illuminate\Support\Collection;

class NewPostNotificationBroadcaster
{
    public function broadcastToFollowers(Collection $followerIds, array $notificationPayload): void
    {
        if (! config('feed.notifications.socket_enabled')) {
            return;
        }

        foreach ($followerIds as $followerId) {
            NewPostNotificationBroadcasted::dispatch((int) $followerId, $notificationPayload);
        }
    }
}
