<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LikeSeeder extends Seeder
{
    public function run(int $postCount, int $userCount, int $requiredLikeCount, int $chunkSize): void
    {
        if ($requiredLikeCount === 0) {
            return;
        }

        $likeRows = [];
        $likeRowsCreated = 0;
        $createdAt = now()->toDateTimeString();
        $firstPostLikeCount = min($userCount, max(1000, intdiv(max(1, $requiredLikeCount), 10)));

        for ($userId = 1; $userId <= $firstPostLikeCount && $likeRowsCreated < $requiredLikeCount; $userId++) {
            $this->addLikeRow($likeRows, 1, $userId, $createdAt, $chunkSize);
            $likeRowsCreated++;
        }

        for ($likeDistance = 1; $likeRowsCreated < $requiredLikeCount; $likeDistance++) {
            if ($likeDistance > $userCount) {
                throw new RuntimeException('Unable to generate the configured number of unique likes.');
            }

            for ($postId = 1; $postId <= $postCount && $likeRowsCreated < $requiredLikeCount; $postId++) {
                $userId = (($postId + $likeDistance - 1) % $userCount) + 1;

                if ($postId === 1 && $userId <= $firstPostLikeCount) {
                    continue;
                }

                $this->addLikeRow($likeRows, $postId, $userId, $createdAt, $chunkSize);
                $likeRowsCreated++;
            }
        }

        $this->insertLikeRows($likeRows);
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

        DB::table('likes')->insertOrIgnore($likeRows);
        $likeRows = [];
    }
}
