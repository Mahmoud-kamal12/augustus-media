<?php

namespace App\Providers;

use App\Models\Like;
use App\Models\Post;
use App\Observers\LikeObserver;
use App\Policies\PostPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Post::class, PostPolicy::class);
        Like::observe(LikeObserver::class);
    }
}
