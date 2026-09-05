<?php

namespace Tests\Feature;

use App\Models\Follow;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FeedApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_feed_requires_authentication(): void
    {
        $this->getJson('/feed')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_feed_returns_followed_posts_newest_first_with_like_summary(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $unfollowedAuthor = User::factory()->create();

        $this->follow($viewer, $author);

        $olderPost = Post::factory()->for($author, 'author')->create([
            'content' => 'Older followed post',
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        $newerPost = Post::factory()->for($author, 'author')->create([
            'content' => 'Newer followed post',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Post::factory()->for($unfollowedAuthor, 'author')->create([
            'content' => 'Hidden unfollowed post',
            'created_at' => now()->addMinute(),
            'updated_at' => now()->addMinute(),
        ]);

        Like::query()->create([
            'post_id' => $newerPost->id,
            'user_id' => $viewer->id,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson('/feed?per_page=10')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newerPost->id)
            ->assertJsonPath('data.0.author.id', $author->id)
            ->assertJsonPath('data.0.likes_count', 1)
            ->assertJsonPath('data.0.is_liked', true)
            ->assertJsonPath('data.1.id', $olderPost->id)
            ->assertJsonPath('data.1.is_liked', false)
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_feed_uses_cursor_pagination(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();

        $this->follow($viewer, $author);

        $oldestPost = Post::factory()->for($author, 'author')->create([
            'created_at' => now()->subMinutes(3),
            'updated_at' => now()->subMinutes(3),
        ]);

        $middlePost = Post::factory()->for($author, 'author')->create([
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);

        $newestPost = Post::factory()->for($author, 'author')->create([
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($viewer);

        $firstPage = $this->getJson('/feed?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newestPost->id)
            ->assertJsonPath('data.1.id', $middlePost->id)
            ->assertJsonPath('meta.has_more_pages', true);

        $nextPageUrl = '/feed?'.http_build_query([
            'per_page' => 2,
            'cursor' => $firstPage->json('meta.next_cursor'),
        ]);

        $this->getJson($nextPageUrl)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $oldestPost->id);
    }

    public function test_feed_rejects_per_page_above_configured_limit(): void
    {
        config()->set('feed.pagination.max_per_page', 5);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/feed?per_page=6')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_feed_first_page_cache_refreshes_after_follow_changes(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->for($author, 'author')->create();

        Sanctum::actingAs($viewer);

        $this->getJson('/feed')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->postJson("/follow/{$author->id}")
            ->assertOk();

        $this->getJson('/feed')
            ->assertOk()
            ->assertJsonPath('data.0.id', $post->id);
    }

    private function follow(User $follower, User $author): void
    {
        Follow::query()->insert([
            'follower_id' => $follower->id,
            'followed_id' => $author->id,
            'created_at' => now(),
        ]);
    }
}
