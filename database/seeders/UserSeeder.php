<?php

namespace Database\Seeders;

use Database\Seeders\Support\BulkInserter;
use Database\Seeders\Support\SeedSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $settings = SeedSettings::fromConfig();
        $writer = new BulkInserter('users', $settings->chunkSize);
        $password = Hash::make('password');
        $now = now()->toDateTimeString();

        for ($id = 1; $id <= $settings->users; $id++) {
            $writer->add([
                'id' => $id,
                'name' => $this->nameFor($id),
                'email' => $this->emailFor($id),
                'password' => $password,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $writer->flushIfFull();
        }

        $writer->flush();
    }

    private function nameFor(int $id): string
    {
        return match ($id) {
            1 => 'Demo User',
            2 => 'Augustus News',
            default => "User {$id}",
        };
    }

    private function emailFor(int $id): string
    {
        return match ($id) {
            1 => 'demo@example.com',
            2 => 'augustus-news@example.com',
            default => "user{$id}@example.com",
        };
    }
}
