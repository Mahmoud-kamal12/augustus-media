<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    private int $chunkSize = 1000;

    public function run(): void
    {
        DB::disableQueryLog();
        Cache::flush();

        $this->chunkSize = max(100, (int) env('SEED_CHUNK_SIZE', 1000));

        $userCount = max(2, (int) env('SEED_USERS', 10000));
        $postCount = max(1, (int) env('SEED_POSTS', 100000));
        $followCount = min((int) env('SEED_FOLLOWS', 200000), $userCount * ($userCount - 1));
        $likeCount = min((int) env('SEED_LIKES', 500000), $postCount * $userCount);

        $this->truncateSeededTables();

        $this->seedUsers($userCount);
        $this->seedPosts($postCount, $userCount);
        $this->seedFollows($followCount, $userCount);
        $this->seedLikes($likeCount, $postCount, $userCount);

        $this->printCounts();
    }

    private function truncateSeededTables(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (['personal_access_tokens', 'likes', 'follows', 'posts', 'users'] as $table) {
            DB::table($table)->truncate();
        }

        Schema::enableForeignKeyConstraints();
    }

    private function seedUsers(int $userCount): void
    {
        $password = Hash::make('password');
        $now = now()->toDateTimeString();
        $rows = [];

        for ($id = 1; $id <= $userCount; $id++) {
            $rows[] = [
                'id' => $id,
                'name' => match ($id) {
                    1 => 'Demo User',
                    2 => 'Augustus News',
                    default => "User {$id}",
                },
                'email' => match ($id) {
                    1 => 'demo@example.com',
                    2 => 'augustus-news@example.com',
                    default => "user{$id}@example.com",
                },
                'password' => $password,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $this->insertWhenFull('users', $rows);
        }

        $this->flushRows('users', $rows);
    }

    private function seedPosts(int $postCount, int $userCount): void
    {
        $baseTime = now()->timestamp;
        $rows = [];

        for ($id = 1; $id <= $postCount; $id++) {
            $createdAt = date('Y-m-d H:i:s', $baseTime - ($id * 37 % (45 * 24 * 60 * 60)));
            $userId = $id === 1
                ? 2
                : ($id % 10 === 0 ? 2 : (($id * 37) % $userCount) + 1);

            $rows[] = [
                'id' => $id,
                'user_id' => $userId,
                'content' => $id === 1
                    ? 'A fast-moving regional story is gathering a huge response across the network.'
                    : "Seed post {$id} covering local news, culture, business, and daily updates.",
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            $this->insertWhenFull('posts', $rows);
        }

        $this->flushRows('posts', $rows);
    }

    private function seedFollows(int $followCount, int $userCount): void
    {
        $now = now()->toDateTimeString();
        $rows = [];
        $seen = [];

        $addFollow = function (int $followerId, int $followedId) use (&$rows, &$seen, $followCount, $now): bool {
            if ($followerId === $followedId || count($seen) >= $followCount) {
                return false;
            }

            $key = "{$followerId}:{$followedId}";

            if (isset($seen[$key])) {
                return false;
            }

            $seen[$key] = true;
            $rows[] = [
                'follower_id' => $followerId,
                'followed_id' => $followedId,
                'created_at' => $now,
            ];

            $this->insertWhenFull('follows', $rows);

            return true;
        };

        for ($followedId = 2; $followedId <= min($userCount, 251); $followedId++) {
            $addFollow(1, $followedId);
        }

        for ($followerId = 3; $followerId <= $userCount && count($seen) < $followCount; $followerId++) {
            $addFollow($followerId, 2);
        }

        for ($round = 0; count($seen) < $followCount; $round++) {
            for ($followerId = 1; $followerId <= $userCount && count($seen) < $followCount; $followerId++) {
                $followedId = (($followerId * 37 + $round * 101) % $userCount) + 1;
                $addFollow($followerId, $followedId);
            }
        }

        $this->flushRows('follows', $rows);
    }

    private function seedLikes(int $likeCount, int $postCount, int $userCount): void
    {
        $now = now()->toDateTimeString();
        $rows = [];
        $seen = [];
        $viralLikes = min($userCount, max(1000, intdiv(max(1, $likeCount), 10)));

        $addLike = function (int $postId, int $userId) use (&$rows, &$seen, $likeCount, $now): bool {
            if (count($seen) >= $likeCount) {
                return false;
            }

            $key = "{$postId}:{$userId}";

            if (isset($seen[$key])) {
                return false;
            }

            $seen[$key] = true;
            $rows[] = [
                'post_id' => $postId,
                'user_id' => $userId,
                'created_at' => $now,
            ];

            $this->insertWhenFull('likes', $rows);

            return true;
        };

        for ($userId = 1; $userId <= $viralLikes; $userId++) {
            $addLike(1, $userId);
        }

        for ($round = 0; count($seen) < $likeCount; $round++) {
            for ($postId = 1; $postId <= $postCount && count($seen) < $likeCount; $postId++) {
                $userId = (($postId * 53 + $round * 97) % $userCount) + 1;
                $addLike($postId, $userId);
            }
        }

        $this->flushRows('likes', $rows);
    }

    private function insertWhenFull(string $table, array &$rows): void
    {
        if (count($rows) >= $this->chunkSize) {
            $this->flushRows($table, $rows);
        }
    }

    private function flushRows(string $table, array &$rows): void
    {
        if ($rows === []) {
            return;
        }

        DB::table($table)->insert($rows);
        $rows = [];
    }

    private function printCounts(): void
    {
        foreach (['users', 'posts', 'follows', 'likes'] as $table) {
            $this->command?->info(sprintf('%s: %s', $table, DB::table($table)->count()));
        }

        $this->command?->info('Demo credentials: demo@example.com / password');
    }
}
