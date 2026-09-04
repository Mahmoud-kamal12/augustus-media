<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserFeedCache
{
    private const FIRST_PAGE_KEY_PREFIX = 'feed:first-page:user';

    private const FIRST_PAGE_VERSION_KEY_PREFIX = 'feed:first-page-version:user';

    public function getFirstPage(User $user, int $postsPerPage): ?array
    {
        return Cache::get($this->firstPageKey($user, $postsPerPage));
    }

    public function storeFirstPage(User $user, int $postsPerPage, array $feedPage): void
    {
        Cache::put($this->firstPageKey($user, $postsPerPage), $feedPage, config('feed.cache.first_page_ttl'));
    }

    public function invalidateFirstPage(User $user): void
    {
        $versionKey = self::FIRST_PAGE_VERSION_KEY_PREFIX.":{$user->id}";
        $currentVersion = (int) Cache::get($versionKey, 1);

        Cache::forever($versionKey, $currentVersion + 1);
    }

    private function firstPageKey(User $user, int $postsPerPage): string
    {
        $versionKey = self::FIRST_PAGE_VERSION_KEY_PREFIX.":{$user->id}";
        $currentVersion = (int) Cache::get($versionKey, 1);

        return self::FIRST_PAGE_KEY_PREFIX.":{$user->id}:per-page:{$postsPerPage}:v{$currentVersion}";
    }
}
