<?php

namespace Database\Seeders;

use App\Models\Like;
use App\Models\Post;
use Illuminate\Database\Seeder;
use RuntimeException;

class LikeSeeder extends Seeder
{
    private const FEATURED_POST_ID = 1;

    private const MINIMUM_FEATURED_POST_LIKES = 1000;

    private const FEATURED_POST_LIKE_RATIO = 10;

    public function run(int $postCount, int $userCount, int $requiredLikeCount, int $chunkSize): void
    {
        if ($requiredLikeCount === 0) {
            return;
        }

        $likeRows = [];
        $likesCountByPostId = [];
        $likeRowsCreated = 0;
        $createdAt = now()->toDateTimeString();
        $featuredPostLikeTargetByRatio = intdiv($requiredLikeCount, self::FEATURED_POST_LIKE_RATIO);
        $featuredPostTargetLikeCount = max(self::MINIMUM_FEATURED_POST_LIKES, $featuredPostLikeTargetByRatio);
        $featuredPostLikeCount = min($userCount, $featuredPostTargetLikeCount);

        for ($userId = 1; $userId <= $featuredPostLikeCount && $likeRowsCreated < $requiredLikeCount; $userId++) {
            $this->addLikeRow($likeRows, $likesCountByPostId, self::FEATURED_POST_ID, $userId, $createdAt, $chunkSize);
            $likeRowsCreated++;
        }

        for ($likeDistance = 1; $likeRowsCreated < $requiredLikeCount; $likeDistance++) {
            if ($likeDistance > $userCount) {
                throw new RuntimeException('Unable to generate the configured number of unique likes.');
            }

            for ($postId = 1; $postId <= $postCount && $likeRowsCreated < $requiredLikeCount; $postId++) {
                $userId = (($postId + $likeDistance - 1) % $userCount) + 1;

                if ($postId === self::FEATURED_POST_ID && $userId <= $featuredPostLikeCount) {
                    continue;
                }

                $this->addLikeRow($likeRows, $likesCountByPostId, $postId, $userId, $createdAt, $chunkSize);
                $likeRowsCreated++;
            }
        }

        $this->insertLikeRows($likeRows);
        $this->updatePostLikeCounts($likesCountByPostId, $chunkSize);
    }

    private function addLikeRow(
        array &$likeRows,
        array &$likesCountByPostId,
        int $postId,
        int $userId,
        string $createdAt,
        int $chunkSize,
    ): void {
        $likeRows[] = [
            'post_id' => $postId,
            'user_id' => $userId,
            'created_at' => $createdAt,
        ];

        $likesCountByPostId[$postId] = ($likesCountByPostId[$postId] ?? 0) + 1;

        if (count($likeRows) >= $chunkSize) {
            $this->insertLikeRows($likeRows);
        }
    }

    private function insertLikeRows(array &$likeRows): void
    {
        if ($likeRows === []) {
            return;
        }

        Like::query()->insertOrIgnore($likeRows);
        $likeRows = [];
    }

    private function updatePostLikeCounts(array $likesCountByPostId, int $chunkSize): void
    {
        if ($likesCountByPostId === []) {
            return;
        }

        $postIdsByLikesCount = [];

        foreach ($likesCountByPostId as $postId => $likesCount) {
            $postIdsByLikesCount[$likesCount][] = $postId;
        }

        foreach ($postIdsByLikesCount as $likesCount => $postIds) {
            foreach (array_chunk($postIds, $chunkSize) as $postIdsChunk) {
                Post::query()
                    ->whereIn('id', $postIdsChunk)
                    ->update(['likes_count' => $likesCount]);
            }
        }
    }
}
