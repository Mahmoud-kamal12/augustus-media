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

        $this->truncateSeededTables();

        $this->call([
            UserSeeder::class,
            PostSeeder::class,
            FollowSeeder::class,
            LikeSeeder::class,
        ]);

        $this->printCounts();
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

    private function printCounts(): void
    {
        foreach (['users', 'posts', 'follows', 'likes'] as $table) {
            $this->command?->info(sprintf('%s: %s', $table, DB::table($table)->count()));
        }

        $this->command?->info('Demo credentials: demo@example.com / password');
    }
}
