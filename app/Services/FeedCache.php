<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class FeedCache
{
    private const FIRST_PAGE_KEY_PREFIX = 'feed:first-page:user';

    private const FIRST_PAGE_KEY_VERSION = 'v1';

    public function firstPageFor(User $user): ?array
    {
        return Cache::get($this->firstPageKey($user));
    }

    public function putFirstPage(User $user, array $feedPage): void
    {
        Cache::put($this->firstPageKey($user), $feedPage, config('feed.cache.first_page_ttl'));
    }

    public function forgetFirstPage(User $user): void
    {
        Cache::forget($this->firstPageKey($user));
    }

    private function firstPageKey(User $user): string
    {
        return self::FIRST_PAGE_KEY_PREFIX.":{$user->id}:".self::FIRST_PAGE_KEY_VERSION;
    }
}
