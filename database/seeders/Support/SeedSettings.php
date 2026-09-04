<?php

namespace Database\Seeders\Support;

final readonly class SeedSettings
{
    public function __construct(
        public int $users,
        public int $posts,
        public int $follows,
        public int $likes,
        public int $chunkSize,
    ) {}

    public static function fromConfig(): self
    {
        $users = max(2, (int) config('seeding.users'));
        $posts = max(1, (int) config('seeding.posts'));

        return new self(
            users: $users,
            posts: $posts,
            follows: max(0, min((int) config('seeding.follows'), $users * ($users - 1))),
            likes: max(0, min((int) config('seeding.likes'), $posts * $users)),
            chunkSize: max(100, (int) config('seeding.chunk_size')),
        );
    }
}
