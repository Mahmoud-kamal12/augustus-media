<?php

namespace Database\Seeders;

use App\Models\Follow;
use Database\Seeders\Support\SeedConfig;
use Database\Seeders\Support\SeedIds;
use Illuminate\Database\Seeder;
use RuntimeException;

class FollowSeeder extends Seeder
{
    private const MAX_DEMO_FOLLOWED_USERS = 250;

    public function run(): void
    {
        $userCount = SeedConfig::userCount();
        $followCount = SeedConfig::followCount();
        $chunkSize = SeedConfig::chunkSize();

        if ($followCount === 0) {
            return;
        }

        $followRows = [];
        $createdFollowCount = 0;
        $createdAt = now()->toDateTimeString();
        $lastDemoFollowedUserId = min($userCount, SeedIds::DEMO_USER + self::MAX_DEMO_FOLLOWED_USERS);

        for ($followedUserId = SeedIds::NEWS_USER; $followedUserId <= $lastDemoFollowedUserId && $createdFollowCount < $followCount; $followedUserId++) {
            $this->addFollowRow($followRows, SeedIds::DEMO_USER, $followedUserId, $createdAt, $chunkSize);
            $createdFollowCount++;
        }

        for ($followerUserId = SeedIds::FIRST_REGULAR_USER; $followerUserId <= $userCount && $createdFollowCount < $followCount; $followerUserId++) {
            $this->addFollowRow($followRows, $followerUserId, SeedIds::NEWS_USER, $createdAt, $chunkSize);
            $createdFollowCount++;
        }

        for ($followDistance = 1; $createdFollowCount < $followCount; $followDistance++) {
            if ($followDistance >= $userCount) {
                throw new RuntimeException('Unable to generate the configured number of unique follows.');
            }

            for ($followerUserId = 1; $followerUserId <= $userCount && $createdFollowCount < $followCount; $followerUserId++) {
                $followedUserId = (($followerUserId + $followDistance - 1) % $userCount) + 1;

                if ($this->shouldSkipFollowPair($followerUserId, $followedUserId, $lastDemoFollowedUserId)) {
                    continue;
                }

                $this->addFollowRow($followRows, $followerUserId, $followedUserId, $createdAt, $chunkSize);
                $createdFollowCount++;
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

    private function shouldSkipFollowPair(int $followerUserId, int $followedUserId, int $lastDemoFollowedUserId): bool
    {
        if ($followerUserId === $followedUserId) {
            return true;
        }

        if ($followerUserId === SeedIds::DEMO_USER && $followedUserId >= SeedIds::NEWS_USER && $followedUserId <= $lastDemoFollowedUserId) {
            return true;
        }

        return $followerUserId >= SeedIds::FIRST_REGULAR_USER && $followedUserId === SeedIds::NEWS_USER;
    }
}
