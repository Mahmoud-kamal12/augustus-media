<?php

namespace Database\Seeders;

use Database\Seeders\Support\BulkInserter;
use Database\Seeders\Support\SeedSettings;
use Illuminate\Database\Seeder;
use RuntimeException;

class FollowSeeder extends Seeder
{
    public function run(): void
    {
        $settings = SeedSettings::fromConfig();

        if ($settings->follows === 0) {
            return;
        }

        $writer = new BulkInserter('follows', $settings->chunkSize, ignoreDuplicates: true);
        $inserted = 0;
        $now = now()->toDateTimeString();

        for ($followedId = 2; $followedId <= min($settings->users, 251) && $inserted < $settings->follows; $followedId++) {
            $inserted += $this->queueFollow($writer, $settings, $inserted, 1, $followedId, $now);
        }

        for ($followerId = 3; $followerId <= $settings->users && $inserted < $settings->follows; $followerId++) {
            $inserted += $this->queueFollow($writer, $settings, $inserted, $followerId, 2, $now);
        }

        for ($offset = 1; $inserted < $settings->follows; $offset++) {
            if ($offset >= $settings->users) {
                throw new RuntimeException('Unable to generate the configured number of unique follows.');
            }

            for ($followerId = 1; $followerId <= $settings->users && $inserted < $settings->follows; $followerId++) {
                $followedId = $this->followedIdFor($followerId, $offset, $settings->users);
                $inserted += $this->queueFollow($writer, $settings, $inserted, $followerId, $followedId, $now);
            }
        }

        $writer->flush();
    }

    private function queueFollow(
        BulkInserter $writer,
        SeedSettings $settings,
        int $inserted,
        int $followerId,
        int $followedId,
        string $createdAt,
    ): int {
        if ($inserted >= $settings->follows || $followerId === $followedId) {
            return 0;
        }

        $writer->add([
            'follower_id' => $followerId,
            'followed_id' => $followedId,
            'created_at' => $createdAt,
        ]);

        return $writer->flushIfFull($settings->follows - $inserted);
    }

    private function followedIdFor(int $followerId, int $offset, int $userCount): int
    {
        return (($followerId + $offset - 1) % $userCount) + 1;
    }
}
