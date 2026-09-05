<?php

namespace Database\Seeders;

use App\Models\Follow;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\Support\SeedIds;
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

        $this->truncateSeededTables();
        $this->call([
            UserSeeder::class,
            PostSeeder::class,
            FollowSeeder::class,
            LikeSeeder::class,
        ]);
        $this->printSeedSummary();
    }

    private function truncateSeededTables(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach (self::SEEDED_MODELS as $modelClass) {
                $model = new $modelClass;
                $table = $model->getTable();

                if (Schema::hasTable($table)) {
                    $modelClass::query()->truncate();
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
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
        $this->command->info('Demo user id: '.SeedIds::DEMO_USER);
        $this->command->info('Augustus News user id: '.SeedIds::NEWS_USER);
    }
}
