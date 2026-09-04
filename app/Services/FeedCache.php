<?php

namespace App\Services;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Cache;

class FeedCache
{
    public function rememberFirstPage(User $user, Closure $callback): array
    {
        return Cache::remember(
            $this->firstPageKey($user),
            config('feed.cache.first_page_ttl'),
            $callback
        );
    }

    public function forgetFirstPage(User $user): void
    {
        Cache::forget($this->firstPageKey($user));
    }

    private function firstPageKey(User $user): string
    {
        return "feed:first-page:user:{$user->id}:v1";
    }
}
