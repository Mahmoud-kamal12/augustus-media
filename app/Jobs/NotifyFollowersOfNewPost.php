<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use App\Notifications\NewPostNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

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

        $notificationDataJson = json_encode(NewPostNotification::databaseData($post), JSON_THROW_ON_ERROR);
        $lastFollowerId = 0;

        while (true) {
            $followerIds = DB::table('follows')
                ->where('followed_id', $post->user_id)
                ->where('follower_id', '>', $lastFollowerId)
                ->orderBy('follower_id')
                ->limit($this->notificationChunkSize())
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

            DB::table('notifications')->insertOrIgnore($notificationRows);

            $lastFollowerId = (int) $followerIds->last();
        }
    }

    private function notificationId(int $postId, int $followerId): string
    {
        $hash = md5("new-post:{$postId}:{$followerId}");

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20)
        );
    }

    private function notificationChunkSize(): int
    {
        return config('feed.notifications.chunk_size');
    }
}
