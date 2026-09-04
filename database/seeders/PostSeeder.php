<?php

namespace Database\Seeders;

use Database\Seeders\Support\BulkInserter;
use Database\Seeders\Support\SeedSettings;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $settings = SeedSettings::fromConfig();
        $writer = new BulkInserter('posts', $settings->chunkSize);
        $baseTime = now()->timestamp;

        for ($id = 1; $id <= $settings->posts; $id++) {
            $createdAt = date('Y-m-d H:i:s', $baseTime - ($id * 37 % (45 * 24 * 60 * 60)));

            $writer->add([
                'id' => $id,
                'user_id' => $this->authorIdFor($id, $settings->users),
                'content' => $this->contentFor($id),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $writer->flushIfFull();
        }

        $writer->flush();
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
}
