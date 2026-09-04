<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LikeService
{
    public function like(User $user, Post $post): bool
    {
        return DB::table('likes')->insertOrIgnore([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]) === 1;
    }

    public function unlike(User $user, Post $post): bool
    {
        return DB::table('likes')
            ->where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->delete() > 0;
    }

    public function countFor(Post $post): int
    {
        return $this->countsFor([$post->id])[$post->id] ?? 0;
    }

    public function countsFor(array $postIds): array
    {
        $postIdsToCount = $this->cleanPostIds($postIds);

        if ($postIdsToCount === []) {
            return [];
        }

        $cacheKeysByPostId = $this->cacheKeysByPostId($postIdsToCount);
        $cachedCountsByKey = Cache::many(array_values($cacheKeysByPostId));
        $likeCountsByPostId = [];
        $postIdsMissingFromCache = [];

        foreach ($cacheKeysByPostId as $postId => $cacheKey) {
            if ($cachedCountsByKey[$cacheKey] !== null) {
                $likeCountsByPostId[$postId] = (int) $cachedCountsByKey[$cacheKey];

                continue;
            }

            $postIdsMissingFromCache[] = $postId;
        }

        if ($postIdsMissingFromCache !== []) {
            $databaseCountsByPostId = DB::table('likes')
                ->select('post_id', DB::raw('COUNT(*) as likes_count'))
                ->whereIn('post_id', $postIdsMissingFromCache)
                ->groupBy('post_id')
                ->pluck('likes_count', 'post_id')
                ->all();

            $valuesToCache = [];

            foreach ($postIdsMissingFromCache as $postId) {
                $likeCount = (int) ($databaseCountsByPostId[$postId] ?? 0);
                $likeCountsByPostId[$postId] = $likeCount;
                $valuesToCache[$cacheKeysByPostId[$postId]] = $likeCount;
            }

            Cache::putMany($valuesToCache, config('feed.cache.like_count_ttl'));
        }

        return $likeCountsByPostId;
    }

    public function isLikedBy(Post $post, User $user): bool
    {
        return in_array($post->id, $this->likedPostIds($user, [$post->id]), true);
    }

    public function likedPostIds(User $user, array $postIds): array
    {
        $postIdsToCheck = $this->cleanPostIds($postIds);

        if ($postIdsToCheck === []) {
            return [];
        }

        $likedPostIds = DB::table('likes')
            ->where('user_id', $user->id)
            ->whereIn('post_id', $postIdsToCheck)
            ->pluck('post_id')
            ->all();

        return $this->cleanPostIds($likedPostIds);
    }

    private function cacheKeysByPostId(array $postIds): array
    {
        $cacheKeysByPostId = [];

        foreach ($postIds as $postId) {
            $cacheKeysByPostId[$postId] = $this->likeCountCacheKey($postId);
        }

        return $cacheKeysByPostId;
    }

    private function likeCountCacheKey(int $postId): string
    {
        return "likes_count:{$postId}";
    }

    private function cleanPostIds(array $postIds): array
    {
        $cleanPostIds = [];

        foreach ($postIds as $postId) {
            $postId = (int) $postId;

            if ($postId > 0) {
                $cleanPostIds[$postId] = $postId;
            }
        }

        return array_values($cleanPostIds);
    }
}
