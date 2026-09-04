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

        $configuredUserCount = config('seeding.users');
        $configuredPostCount = config('seeding.posts');
        $configuredFollowCount = config('seeding.follows');
        $configuredLikeCount = config('seeding.likes');
        $configuredChunkSize = config('seeding.chunk_size');

        $userCount = max(2, $configuredUserCount);
        $postCount = max(1, $configuredPostCount);

        $maximumFollowCount = $userCount * ($userCount - 1);
        $maximumLikeCount = $postCount * $userCount;

        $followCount = min($configuredFollowCount, $maximumFollowCount);
        $followCount = max(0, $followCount);

        $likeCount = min($configuredLikeCount, $maximumLikeCount);
        $likeCount = max(0, $likeCount);

        $insertChunkSize = max(100, $configuredChunkSize);

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
            'requiredFollowCount' => $followCount,
            'chunkSize' => $insertChunkSize,
        ]);

        $this->callWith(LikeSeeder::class, [
            'postCount' => $postCount,
            'userCount' => $userCount,
            'requiredLikeCount' => $likeCount,
            'chunkSize' => $insertChunkSize,
        ]);

        $this->printSeedSummary();
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
        if (! $this->command) {
            return;
        }

        foreach (['users', 'posts', 'follows', 'likes'] as $table) {
            $rowCount = DB::table($table)->count();
            $this->command->info("{$table}: {$rowCount}");
        }

        $this->command->info('Demo credentials: demo@example.com / password');
    }
}
