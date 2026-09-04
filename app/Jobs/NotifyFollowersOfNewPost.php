<?php

namespace App\Jobs;

use App\Models\Follow;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewPostNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\DatabaseNotification;

class NotifyFollowersOfNewPost implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $postId) {}

    public function tries(): int
    {
        return config('feed.notifications.tries');
    }

    public function handle(): void
    {
        $post = Post::query()
            ->with('author:id,name')
            ->find($this->postId);

        if (! $post) {
            return;
        }

        $notificationData = NewPostNotification::databaseData($post);
        $notificationDataJson = json_encode($notificationData, JSON_THROW_ON_ERROR);
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

            $createdAt = now()->toDateTimeString();
            $notificationRows = [];

            foreach ($followerIds as $followerId) {
                $notificationRows[] = [
                    'id' => $this->notificationId($post->id, $followerId),
                    'type' => NewPostNotification::class,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $followerId,
                    'data' => $notificationDataJson,
                    'read_at' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }

            DatabaseNotification::query()->insertOrIgnore($notificationRows);

            $lastFollowerId = (int) $followerIds->last();
        }
    }

    private function notificationId(int $postId, int $followerId): string
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
