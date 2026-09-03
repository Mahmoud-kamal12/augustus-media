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

    private const CHUNK_SIZE = 1000;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $postId) {}

    public function handle(): void
    {
        $post = Post::query()
            ->with('author:id,name')
            ->find($this->postId);

        if (! $post) {
            return;
        }

        $payload = json_encode(NewPostNotification::payload($post), JSON_THROW_ON_ERROR);
        $lastFollowerId = 0;

        while (true) {
            $followerIds = DB::table('follows')
                ->where('followed_id', $post->user_id)
                ->where('follower_id', '>', $lastFollowerId)
                ->orderBy('follower_id')
                ->limit(self::CHUNK_SIZE)
                ->pluck('follower_id');

            if ($followerIds->isEmpty()) {
                return;
            }

            $now = now()->toDateTimeString();
            $rows = $followerIds->map(fn (int $followerId): array => [
                'id' => $this->notificationId($post->id, $followerId),
                'type' => NewPostNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $followerId,
                'data' => $payload,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            DB::table('notifications')->insertOrIgnore($rows);

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
}
