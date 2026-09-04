<?php

namespace Database\Seeders;

use App\Models\Follow;
use Illuminate\Database\Seeder;
use RuntimeException;

class FollowSeeder extends Seeder
{
    private const DEMO_USER_ID = 1;

    private const NEWS_USER_ID = 2;

    private const FIRST_REGULAR_USER_ID = 3;

    private const MAX_DEMO_FOLLOWED_USERS = 250;

    public function run(int $userCount, int $requiredFollowCount, int $chunkSize): void
    {
        if ($requiredFollowCount === 0) {
            return;
        }

        $followRows = [];
        $createdFollowPairs = [];
        $followRowsCreated = 0;
        $createdAt = now()->toDateTimeString();
        $lastDemoFollowedUserId = min($userCount, self::MAX_DEMO_FOLLOWED_USERS + 1);

        for ($followedUserId = self::NEWS_USER_ID; $followedUserId <= $lastDemoFollowedUserId && $followRowsCreated < $requiredFollowCount; $followedUserId++) {
            $this->addFollowRow($followRows, self::DEMO_USER_ID, $followedUserId, $createdAt, $chunkSize);
            $createdFollowPairs[self::DEMO_USER_ID][$followedUserId] = true;
            $followRowsCreated++;
        }

        for ($followerUserId = self::FIRST_REGULAR_USER_ID; $followerUserId <= $userCount && $followRowsCreated < $requiredFollowCount; $followerUserId++) {
            $this->addFollowRow($followRows, $followerUserId, self::NEWS_USER_ID, $createdAt, $chunkSize);
            $createdFollowPairs[$followerUserId][self::NEWS_USER_ID] = true;
            $followRowsCreated++;
        }

        for ($followDistance = 1; $followRowsCreated < $requiredFollowCount; $followDistance++) {
            if ($followDistance >= $userCount) {
                throw new RuntimeException('Unable to generate the configured number of unique follows.');
            }

            for ($followerUserId = 1; $followerUserId <= $userCount && $followRowsCreated < $requiredFollowCount; $followerUserId++) {
                $followedUserId = (($followerUserId + $followDistance - 1) % $userCount) + 1;

                if (isset($createdFollowPairs[$followerUserId][$followedUserId])) {
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

        Follow::query()->insertOrIgnore($followRows);
        $followRows = [];
    }
}
