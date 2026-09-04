<?php

namespace Database\Seeders;

use Database\Seeders\Support\BulkInserter;
use Database\Seeders\Support\SeedSettings;
use Illuminate\Database\Seeder;
use RuntimeException;

class LikeSeeder extends Seeder
{
    public function run(): void
    {
        $settings = SeedSettings::fromConfig();

        if ($settings->likes === 0) {
            return;
        }

        $writer = new BulkInserter('likes', $settings->chunkSize, ignoreDuplicates: true);
        $inserted = 0;
        $now = now()->toDateTimeString();
        $viralLikes = min($settings->users, max(1000, intdiv(max(1, $settings->likes), 10)));

        for ($userId = 1; $userId <= $viralLikes && $inserted < $settings->likes; $userId++) {
            $inserted += $this->queueLike($writer, $settings, $inserted, 1, $userId, $now);
        }

        for ($offset = 1; $inserted < $settings->likes; $offset++) {
            if ($offset > $settings->users) {
                throw new RuntimeException('Unable to generate the configured number of unique likes.');
            }

            for ($postId = 1; $postId <= $settings->posts && $inserted < $settings->likes; $postId++) {
                $userId = $this->userIdFor($postId, $offset, $settings->users);
                $inserted += $this->queueLike($writer, $settings, $inserted, $postId, $userId, $now);
            }
        }

        $writer->flush();
    }

    private function queueLike(
        BulkInserter $writer,
        SeedSettings $settings,
        int $inserted,
        int $postId,
        int $userId,
        string $createdAt,
    ): int {
        if ($inserted >= $settings->likes) {
            return 0;
        }

        $writer->add([
            'post_id' => $postId,
            'user_id' => $userId,
            'created_at' => $createdAt,
        ]);

        return $writer->flushIfFull($settings->likes - $inserted);
    }

    private function userIdFor(int $postId, int $offset, int $userCount): int
    {
        return (($postId + $offset - 1) % $userCount) + 1;
    }
}
