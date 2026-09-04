<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostSeeder extends Seeder
{
    public function run(int $postCount, int $userCount, int $chunkSize): void
    {
        $baseTime = now()->timestamp;
        $postRows = [];

        for ($postId = 1; $postId <= $postCount; $postId++) {
            $createdAt = date('Y-m-d H:i:s', $baseTime - ($postId * 37 % (45 * 24 * 60 * 60)));

            $postRows[] = [
                'id' => $postId,
                'user_id' => $this->authorIdFor($postId, $userCount),
                'content' => $this->contentFor($postId),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if (count($postRows) >= $chunkSize) {
                $this->insertPostRows($postRows);
            }
        }

        $this->insertPostRows($postRows);
    }

    private function authorIdFor(int $postId, int $userCount): int
    {
        if ($postId === 1 || $postId % 10 === 0) {
            return 2;
        }

        return (($postId * 37) % $userCount) + 1;
    }

    private function contentFor(int $postId): string
    {
        if ($postId === 1) {
            return 'A fast-moving regional story is gathering a huge response across the network.';
        }

        return "Seed post {$postId} covering local news, culture, business, and daily updates.";
    }

    private function insertPostRows(array &$postRows): void
    {
        if ($postRows === []) {
            return;
        }

        DB::table('posts')->insert($postRows);
        $postRows = [];
    }
}
