<?php

namespace App\Services;

use App\Events\NewPostNotificationBroadcasted;
use Illuminate\Support\Collection;

class NewPostNotificationBroadcaster
{
    public function broadcastToFollowers(Collection $followerIds, array $notificationPayload): void
    {
        if (! config('feed.notifications.broadcast_enabled')) {
            return;
        }

        if ($followerIds->isEmpty()) {
            return;
        }

        $followerUserIds = [];

        foreach ($followerIds as $followerId) {
            $followerUserIds[] = (int) $followerId;
        }

        NewPostNotificationBroadcasted::dispatch($followerUserIds, $notificationPayload);
    }
}
