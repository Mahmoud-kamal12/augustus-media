<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FollowSeeder extends Seeder
{
    public function run(int $userCount, int $requiredFollowCount, int $chunkSize): void
    {
        if ($requiredFollowCount === 0) {
            return;
        }

        $followRows = [];
        $specialFollowPairs = [];
        $followRowsCreated = 0;
        $createdAt = now()->toDateTimeString();
        $lastSpecialFollowedUserId = min($userCount, 251);

        for ($followedUserId = 2; $followedUserId <= $lastSpecialFollowedUserId && $followRowsCreated < $requiredFollowCount; $followedUserId++) {
            $this->addFollowRow($followRows, 1, $followedUserId, $createdAt, $chunkSize);
            $specialFollowPairs[1][$followedUserId] = true;
            $followRowsCreated++;
        }

        for ($followerUserId = 3; $followerUserId <= $userCount && $followRowsCreated < $requiredFollowCount; $followerUserId++) {
            $this->addFollowRow($followRows, $followerUserId, 2, $createdAt, $chunkSize);
            $specialFollowPairs[$followerUserId][2] = true;
            $followRowsCreated++;
        }

        for ($followDistance = 1; $followRowsCreated < $requiredFollowCount; $followDistance++) {
            if ($followDistance >= $userCount) {
                throw new RuntimeException('Unable to generate the configured number of unique follows.');
            }

            for ($followerUserId = 1; $followerUserId <= $userCount && $followRowsCreated < $requiredFollowCount; $followerUserId++) {
                $followedUserId = (($followerUserId + $followDistance - 1) % $userCount) + 1;

                if (isset($specialFollowPairs[$followerUserId][$followedUserId])) {
                    continue;
                }

                $this->addFollowRow($followRows, $followerUserId, $followedUserId, $createdAt, $chunkSize);
                $followRowsCreated++;
            }
        }

        $this->insertFollowRows($followRows);
    }

    private function addFollowRow(
        array &$followRows,
        int $followerUserId,
        int $followedUserId,
        string $createdAt,
        int $chunkSize,
    ): void {
        $followRows[] = [
            'follower_id' => $followerUserId,
            'followed_id' => $followedUserId,
            'created_at' => $createdAt,
        ];

        if (count($followRows) >= $chunkSize) {
            $this->insertFollowRows($followRows);
        }
    }

    private function insertFollowRows(array &$followRows): void
    {
        if ($followRows === []) {
            return;
        }

        DB::table('follows')->insertOrIgnore($followRows);
        $followRows = [];
    }
}
