<?php

namespace Database\Seeders;

use App\Models\Post;
use Database\Seeders\Support\SeedConfig;
use Database\Seeders\Support\SeedIds;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    private const NEWS_AUTHOR_POST_INTERVAL = 10;

    private const POST_SPREAD_SECONDS_STEP = 37;

    private const POST_SPREAD_DAYS = 45;

    public function run(): void
    {
        $postCount = SeedConfig::postCount();
        $userCount = SeedConfig::userCount();
        $chunkSize = SeedConfig::chunkSize();
        $baseTimestamp = now()->timestamp;
        $secondsInPostSpreadWindow = self::POST_SPREAD_DAYS * 24 * 60 * 60;
        $postRows = [];

        for ($postId = 1; $postId <= $postCount; $postId++) {
            $createdAtOffset = ($postId * self::POST_SPREAD_SECONDS_STEP) % $secondsInPostSpreadWindow;
            $createdAtTimestamp = $baseTimestamp - $createdAtOffset;
            $createdAt = date('Y-m-d H:i:s', $createdAtTimestamp);

            $postRows[] = [
                'id' => $postId,
                'user_id' => $this->authorIdFor($postId, $userCount),
                'content' => $this->contentFor($postId),
                'likes_count' => 0,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if (count($postRows) >= $chunkSize) {
                $this->insertPostRows($postRows);
            }
        }

        $this->insertPostRows($postRows);
    }

    private function insertPostRows(array &$postRows): void
    {
        if ($postRows === []) {
            return;
        }

        Post::query()->insert($postRows);
        $postRows = [];
    }

    private function authorIdFor(int $postId, int $userCount): int
    {
        if ($postId === SeedIds::FEATURED_POST || $postId % self::NEWS_AUTHOR_POST_INTERVAL === 0) {
            return SeedIds::NEWS_USER;
        }

        return (($postId * self::POST_SPREAD_SECONDS_STEP) % $userCount) + 1;
    }

    private function contentFor(int $postId): string
    {
        if ($postId === SeedIds::FEATURED_POST) {
            return 'A fast-moving regional story is gathering a huge response across the network.';
        }

        return "Seed post {$postId} covering local news, culture, business, and daily updates.";
    }
}
