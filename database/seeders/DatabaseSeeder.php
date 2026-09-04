<?php

namespace Database\Seeders;

use App\Models\Follow;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;

class DatabaseSeeder extends Seeder
{
    private const SEEDED_MODELS = [
        PersonalAccessToken::class,
        DatabaseNotification::class,
        Like::class,
        Follow::class,
        Post::class,
        User::class,
    ];

    private const SUMMARY_MODELS = [
        User::class,
        Post::class,
        Follow::class,
        Like::class,
    ];

    public function run(): void
    {
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

        foreach (self::SEEDED_MODELS as $modelClass) {
            $model = new $modelClass;
            $table = $model->getTable();

            if (Schema::hasTable($table)) {
                $modelClass::query()->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    private function printSeedSummary(): void
    {
        if (! $this->command) {
            return;
        }

        foreach (self::SUMMARY_MODELS as $modelClass) {
            $model = new $modelClass;
            $table = $model->getTable();
            $rowCount = $modelClass::query()->count();

            $this->command->info("{$table}: {$rowCount}");
        }

        $this->command->info('Demo credentials: demo@example.com / password');
    }
}
