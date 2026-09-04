<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    private const SEEDED_TABLES = [
        'personal_access_tokens',
        'notifications',
        'likes',
        'follows',
        'posts',
        'users',
    ];

    public function run(): void
    {
        DB::disableQueryLog();
        Cache::flush();

        $userCount = $this->userCountFromConfig();
        $postCount = $this->postCountFromConfig();
        $insertChunkSize = $this->insertChunkSizeFromConfig();

        $this->truncateSeededTables();

        $this->callWith(UserSeeder::class, [
            'userCount' => $userCount,
            'chunkSize' => $insertChunkSize,
        ]);

        $this->callWith(PostSeeder::class, [
            'postCount' => $postCount,
            'userCount' => $userCount,
            'chunkSize' => $insertChunkSize,
        ]);

        $this->callWith(FollowSeeder::class, [
            'userCount' => $userCount,
            'requiredFollowCount' => $this->followCountFromConfig($userCount),
            'chunkSize' => $insertChunkSize,
        ]);

        $this->callWith(LikeSeeder::class, [
            'postCount' => $postCount,
            'userCount' => $userCount,
            'requiredLikeCount' => $this->likeCountFromConfig($postCount, $userCount),
            'chunkSize' => $insertChunkSize,
        ]);

        $this->printSeedSummary();
    }

    private function userCountFromConfig(): int
    {
        return max(2, config('seeding.users'));
    }

    private function postCountFromConfig(): int
    {
        return max(1, config('seeding.posts'));
    }

    private function followCountFromConfig(int $userCount): int
    {
        return max(0, min(config('seeding.follows'), $userCount * ($userCount - 1)));
    }

    private function likeCountFromConfig(int $postCount, int $userCount): int
    {
        return max(0, min(config('seeding.likes'), $postCount * $userCount));
    }

    private function insertChunkSizeFromConfig(): int
    {
        return max(100, config('seeding.chunk_size'));
    }

    private function truncateSeededTables(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (self::SEEDED_TABLES as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    private function printSeedSummary(): void
    {
        foreach (['users', 'posts', 'follows', 'likes'] as $table) {
            $this->command?->info(sprintf('%s: %s', $table, DB::table($table)->count()));
        }

        $this->command?->info('Demo credentials: demo@example.com / password');
    }
}
