<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Support\SeedConfig;
use Database\Seeders\Support\SeedIds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $userCount = SeedConfig::userCount();
        $chunkSize = SeedConfig::chunkSize();
        $password = Hash::make('password');
        $createdAt = now()->toDateTimeString();
        $userRows = [];

        for ($userId = 1; $userId <= $userCount; $userId++) {
            $userRows[] = [
                'id' => $userId,
                'name' => $this->nameFor($userId),
                'email' => $this->emailFor($userId),
                'password' => $password,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if (count($userRows) >= $chunkSize) {
                $this->insertUserRows($userRows);
            }
        }

        $this->insertUserRows($userRows);
    }

    private function insertUserRows(array &$userRows): void
    {
        if ($userRows === []) {
            return;
        }

        User::query()->insert($userRows);
        $userRows = [];
    }

    private function nameFor(int $userId): string
    {
        return match ($userId) {
            SeedIds::DEMO_USER => 'Demo User',
            SeedIds::NEWS_USER => 'Augustus News',
            default => "User {$userId}",
        };
    }

    private function emailFor(int $userId): string
    {
        return match ($userId) {
            SeedIds::DEMO_USER => 'demo@example.com',
            SeedIds::NEWS_USER => 'augustus-news@example.com',
            default => "user{$userId}@example.com",
        };
    }
}
