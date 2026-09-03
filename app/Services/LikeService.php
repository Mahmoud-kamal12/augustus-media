<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LikeService
{
    private const COUNT_TTL_SECONDS = 10;

    public function like(User $user, Post $post): bool
    {
        $inserted = DB::table('likes')->insertOrIgnore([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]) === 1;

        if ($inserted) {
            Cache::forget($this->countKey($post->id));
        }

        return $inserted;
    }

    public function unlike(User $user, Post $post): bool
    {
        $deleted = DB::table('likes')
            ->where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->delete() > 0;

        if ($deleted) {
            Cache::forget($this->countKey($post->id));
        }

        return $deleted;
    }

    public function countsFor(iterable $postIds): array
    {
        $ids = Collection::make($postIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $keys = $ids->mapWithKeys(fn (int $id) => [$this->countKey($id) => $id]);
        $cached = Cache::many($keys->keys()->all());
        $counts = [];
        $missingIds = [];

        foreach ($keys as $key => $id) {
            if ($cached[$key] !== null) {
                $counts[$id] = (int) $cached[$key];

                continue;
            }

            $missingIds[] = $id;
        }

        if ($missingIds !== []) {
            $freshCounts = DB::table('likes')
                ->select('post_id', DB::raw('COUNT(*) as likes_count'))
                ->whereIn('post_id', $missingIds)
                ->groupBy('post_id')
                ->pluck('likes_count', 'post_id');

            $valuesToCache = [];

            foreach ($missingIds as $id) {
                $count = (int) $freshCounts->get($id, 0);
                $counts[$id] = $count;
                $valuesToCache[$this->countKey($id)] = $count;
            }

            Cache::putMany($valuesToCache, self::COUNT_TTL_SECONDS);
        }

        return $counts;
    }

    public function likedPostIds(User $user, iterable $postIds): array
    {
        $ids = Collection::make($postIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return DB::table('likes')
            ->where('user_id', $user->id)
            ->whereIn('post_id', $ids)
            ->pluck('post_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function countKey(int $postId): string
    {
        return "likes_count:{$postId}";
    }
}
