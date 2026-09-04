<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(int $userCount, int $chunkSize): void
    {
        $password = Hash::make('password');
        $createdAt = now()->toDateTimeString();
        $userRows = [];

        for ($userId = 1; $userId <= $userCount; $userId++) {
            $name = match ($userId) {
                1 => 'Demo User',
                2 => 'Augustus News',
                default => "User {$userId}",
            };

            $email = match ($userId) {
                1 => 'demo@example.com',
                2 => 'augustus-news@example.com',
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

        DB::table('users')->insert($userRows);
        $userRows = [];
    }
}
