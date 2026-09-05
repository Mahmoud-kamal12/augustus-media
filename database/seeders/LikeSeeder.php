<?php

namespace Database\Seeders;

use App\Models\Like;
use App\Models\Post;
use Database\Seeders\Support\SeedConfig;
use Database\Seeders\Support\SeedIds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LikeSeeder extends Seeder
{
    private const MINIMUM_FEATURED_POST_LIKES = 1000;

    private const FEATURED_POST_LIKE_RATIO = 10;

    public function run(): void
    {
        $postCount = SeedConfig::postCount();
        $userCount = SeedConfig::userCount();
        $likeCount = SeedConfig::likeCount();
        $chunkSize = SeedConfig::chunkSize();

        if ($likeCount === 0) {
            return;
        }

        $likeRows = [];
        $createdLikeCount = 0;
        $createdAt = now()->toDateTimeString();
        $featuredPostLikeTargetByRatio = intdiv($likeCount, self::FEATURED_POST_LIKE_RATIO);
        $featuredPostTargetLikeCount = max(self::MINIMUM_FEATURED_POST_LIKES, $featuredPostLikeTargetByRatio);
        $featuredPostLikeCount = min($userCount, $featuredPostTargetLikeCount);
        $featuredPostLikesCreated = 0;

        for ($userId = 1; $userId <= $featuredPostLikeCount && $createdLikeCount < $likeCount; $userId++) {
            $this->addLikeRow($likeRows, SeedIds::FEATURED_POST, $userId, $createdAt, $chunkSize);
            $createdLikeCount++;
            $featuredPostLikesCreated++;
        }

        for ($likeDistance = 1; $createdLikeCount < $likeCount; $likeDistance++) {
            if ($likeDistance > $userCount) {
                throw new RuntimeException('Unable to generate the configured number of unique likes.');
            }

            for ($postId = 1; $postId <= $postCount && $createdLikeCount < $likeCount; $postId++) {
                $userId = (($postId + $likeDistance - 1) % $userCount) + 1;

                if ($postId === SeedIds::FEATURED_POST && $userId <= $featuredPostLikesCreated) {
                    continue;
                }

                $this->addLikeRow($likeRows, $postId, $userId, $createdAt, $chunkSize);
                $createdLikeCount++;
            }
        }

        $this->insertLikeRows($likeRows);
        $this->refreshPostLikeCounts();
    }

    private function addLikeRow(
        array &$likeRows,
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

    private function refreshPostLikeCounts(): void
    {
        $postTable = Post::TABLE;
        $likeTable = Like::TABLE;

        Post::query()->update([
            'likes_count' => DB::raw("(select count(*) from {$likeTable} where {$likeTable}.post_id = {$postTable}.id)"),
        ]);
    }
}
