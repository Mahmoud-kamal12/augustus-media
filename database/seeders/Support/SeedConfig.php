<?php

namespace Database\Seeders\Support;

final class SeedConfig
{
    private const MINIMUM_USERS = 2;

    private const MINIMUM_POSTS = 1;

    private const MINIMUM_CHUNK_SIZE = 100;

    public static function userCount(): int
    {
        return max(self::MINIMUM_USERS, (int) config('seeding.users'));
    }

    public static function postCount(): int
    {
        return max(self::MINIMUM_POSTS, (int) config('seeding.posts'));
    }

    public static function followCount(): int
    {
        $userCount = self::userCount();
        $maximumFollowCount = $userCount * ($userCount - 1);
        $configuredFollowCount = max(0, (int) config('seeding.follows'));

        return min($configuredFollowCount, $maximumFollowCount);
    }

    public static function likeCount(): int
    {
        $maximumLikeCount = self::postCount() * self::userCount();
        $configuredLikeCount = max(0, (int) config('seeding.likes'));

        return min($configuredLikeCount, $maximumLikeCount);
    }

    public static function chunkSize(): int
    {
        return max(self::MINIMUM_CHUNK_SIZE, (int) config('seeding.chunk_size'));
    }
}
