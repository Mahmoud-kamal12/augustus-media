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
            $authorId = (($postId * 37) % $userCount) + 1;
            $content = "Seed post {$postId} covering local news, culture, business, and daily updates.";

            if ($postId === 1 || $postId % 10 === 0) {
                $authorId = 2;
            }

            if ($postId === 1) {
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

        DB::table('posts')->insert($postRows);
        $postRows = [];
    }
}
