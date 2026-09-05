<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Notifications\NewPostNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class NewPostNotificationStore
{
    public function storeForFollowers(Post $post, Collection $followerIds, array $notificationPayload): void
    {
        if ($followerIds->isEmpty()) {
            return;
        }

        $notificationPayloadJson = json_encode($notificationPayload, JSON_THROW_ON_ERROR);
        $createdAt = now()->toDateTimeString();
        $notificationRows = [];

        foreach ($followerIds as $followerId) {
            $followerId = (int) $followerId;

            $notificationRows[] = [
                'id' => $this->stableNotificationId($post->id, $followerId),
                'type' => NewPostNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $followerId,
                'data' => $notificationPayloadJson,
                'read_at' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }

        DatabaseNotification::query()->insertOrIgnore($notificationRows);
    }

    private function stableNotificationId(int $postId, int $followerId): string
    {
        $hash = md5("new-post:{$postId}:{$followerId}");
        $firstPart = substr($hash, 0, 8);
        $secondPart = substr($hash, 8, 4);
        $thirdPart = substr($hash, 12, 4);
        $fourthPart = substr($hash, 16, 4);
        $lastPart = substr($hash, 20);

        return "{$firstPart}-{$secondPart}-{$thirdPart}-{$fourthPart}-{$lastPart}";
    }
}
