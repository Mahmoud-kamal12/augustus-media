<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    private const FEATURED_POST_ID = 1;

    private const NEWS_AUTHOR_ID = 2;

    private const NEWS_AUTHOR_POST_INTERVAL = 10;

    private const POST_SPREAD_SECONDS_STEP = 37;

    private const POST_SPREAD_DAYS = 45;

    public function run(int $postCount, int $userCount, int $chunkSize): void
    {
        $baseTimestamp = now()->timestamp;
        $secondsInPostSpreadWindow = self::POST_SPREAD_DAYS * 24 * 60 * 60;
        $postRows = [];

        for ($postId = 1; $postId <= $postCount; $postId++) {
            $createdAtOffset = ($postId * self::POST_SPREAD_SECONDS_STEP) % $secondsInPostSpreadWindow;
            $createdAtTimestamp = $baseTimestamp - $createdAtOffset;
            $createdAt = date('Y-m-d H:i:s', $createdAtTimestamp);
            $authorId = (($postId * self::POST_SPREAD_SECONDS_STEP) % $userCount) + 1;
            $content = "Seed post {$postId} covering local news, culture, business, and daily updates.";

            if ($postId === self::FEATURED_POST_ID || $postId % self::NEWS_AUTHOR_POST_INTERVAL === 0) {
                $authorId = self::NEWS_AUTHOR_ID;
            }

            if ($postId === self::FEATURED_POST_ID) {
                $content = 'A fast-moving regional story is gathering a huge response across the network.';
            }

            $postRows[] = [
                'id' => $postId,
                'user_id' => $authorId,
                'content' => $content,
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
}
