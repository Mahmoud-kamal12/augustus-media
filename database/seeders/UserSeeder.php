<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private const DEMO_USER_ID = 1;

    private const NEWS_USER_ID = 2;

    public function run(int $userCount, int $chunkSize): void
    {
        $password = Hash::make('password');
        $createdAt = now()->toDateTimeString();
        $userRows = [];

        for ($userId = 1; $userId <= $userCount; $userId++) {
            $name = match ($userId) {
                self::DEMO_USER_ID => 'Demo User',
                self::NEWS_USER_ID => 'Augustus News',
                default => "User {$userId}",
            };

            $email = match ($userId) {
                self::DEMO_USER_ID => 'demo@example.com',
                self::NEWS_USER_ID => 'augustus-news@example.com',
                default => "user{$userId}@example.com",
            };

            $userRows[] = [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
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
}
