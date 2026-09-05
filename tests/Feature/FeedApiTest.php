<?php

namespace Tests\Feature;

use App\Events\NewPostNotificationBroadcasted;
use App\Jobs\NotifyFollowersOfNewPost;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewPostNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
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

    public function test_user_can_register_and_login(): void
    {
        $this->postJson('/register', [
            'name' => 'Mona Example',
            'email' => 'mona@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'created_at'],
                    'token',
                ],
                'meta',
            ]);

        $this->postJson('/login', [
            'email' => 'mona@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'created_at'],
                    'token',
                ],
                'meta',
            ]);
    }

    public function test_follow_self_is_rejected_and_follow_is_duplicate_safe(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();

        Sanctum::actingAs($viewer);

        $this->postJson("/follow/{$viewer->id}")
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('user_id');

        $this->postJson("/follow/{$author->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.followed', true);

        $this->postJson("/follow/{$author->id}")
            ->assertOk()
            ->assertJsonPath('data.followed', true);

        $this->assertDatabaseCount(Follow::class, 1);
    }

    public function test_feed_returns_followed_posts_newest_first_with_like_state(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $unfollowed = User::factory()->create();

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
        Post::factory()->for($unfollowed, 'author')->create([
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
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_feed_rejects_per_page_above_configured_limit(): void
    {
        config()->set('feed.pagination.max_per_page', 5);

        $viewer = User::factory()->create();

        Sanctum::actingAs($viewer);

        $this->getJson('/feed?per_page=6')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_first_feed_page_cache_keeps_each_page_size_separate(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();

        $this->follow($viewer, $author);

        Post::factory()->count(2)->for($author, 'author')->create();

        Sanctum::actingAs($viewer);

        $this->getJson('/feed?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1);

        $this->getJson('/feed?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    }

    public function test_follow_invalidates_cached_first_feed_page(): void
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

    public function test_current_user_uses_standard_response_envelope(): void
    {
        $viewer = User::factory()->create();

        Sanctum::actingAs($viewer);

        $this->getJson('/user')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $viewer->id)
            ->assertJsonStructure(['message', 'data', 'meta']);
    }

    public function test_like_is_duplicate_safe_and_unlike_is_idempotent(): void
    {
        $viewer = User::factory()->create();
        $post = Post::factory()->create();

        Sanctum::actingAs($viewer);

        $this->postJson("/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 1)
            ->assertJsonPath('data.is_liked', true);

        $this->postJson("/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 1)
            ->assertJsonPath('data.is_liked', true);

        $this->assertDatabaseCount(Like::class, 1);
        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'likes_count' => 1,
        ]);

        $this->deleteJson("/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('data.is_liked', false)
            ->assertJsonStructure(['data' => ['likes_count']]);

        $this->deleteJson("/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('data.is_liked', false)
            ->assertJsonStructure(['data' => ['likes_count']]);

        $this->assertDatabaseCount(Like::class, 0);
        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'likes_count' => 0,
        ]);
    }

    public function test_post_show_returns_current_like_summary(): void
    {
        $viewer = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create();

        Like::query()->create([
            'post_id' => $post->id,
            'user_id' => $viewer->id,
            'created_at' => now(),
        ]);

        Like::query()->create([
            'post_id' => $post->id,
            'user_id' => $otherUser->id,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson("/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 2)
            ->assertJsonPath('data.is_liked', true);
    }

    public function test_post_show_reads_user_from_api_token_when_present(): void
    {
        $viewer = User::factory()->create();
        $post = Post::factory()->create();
        $token = $viewer->createToken('api')->plainTextToken;

        Like::query()->create([
            'post_id' => $post->id,
            'user_id' => $viewer->id,
            'created_at' => now(),
        ]);

        $this->withToken($token)
            ->getJson("/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.is_liked', true);
    }

    public function test_guest_can_show_post_with_not_liked_state(): void
    {
        $post = Post::factory()->create();

        $this->getJson("/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 0)
            ->assertJsonPath('data.is_liked', false);
    }

    public function test_database_seeder_updates_stored_like_counts(): void
    {
        config()->set('seeding.users', 10);
        config()->set('seeding.posts', 20);
        config()->set('seeding.follows', 30);
        config()->set('seeding.likes', 40);
        config()->set('seeding.chunk_size', 10);

        $this->seed();

        $storedLikesCount = Post::query()->sum('likes_count');
        $likesCount = Like::query()->count();

        $this->assertSame($likesCount, (int) $storedLikesCount);
    }

    public function test_user_cannot_delete_another_users_post(): void
    {
        $viewer = User::factory()->create();
        $post = Post::factory()->create();

        Sanctum::actingAs($viewer);

        $this->deleteJson("/posts/{$post->id}")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['message', 'data', 'meta', 'errors']);

        $this->assertDatabaseHas(Post::class, ['id' => $post->id]);
    }

    public function test_user_can_delete_own_post_with_standard_response_envelope(): void
    {
        $viewer = User::factory()->create();
        $post = Post::factory()->for($viewer, 'author')->create();

        Sanctum::actingAs($viewer);

        $this->deleteJson("/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null)
            ->assertJsonStructure(['message', 'data', 'meta']);

        $this->assertDatabaseMissing(Post::class, ['id' => $post->id]);
    }

    public function test_notification_job_writes_one_record_per_follower_idempotently(): void
    {
        $author = User::factory()->create();
        $followers = User::factory()->count(2)->create();
        $post = Post::factory()->for($author, 'author')->create();

        foreach ($followers as $follower) {
            $this->follow($follower, $author);
        }

        Event::fake([NewPostNotificationBroadcasted::class]);

        $firstJobRun = new NotifyFollowersOfNewPost($post->id);
        app()->call([$firstJobRun, 'handle']);

        $secondJobRun = new NotifyFollowersOfNewPost($post->id);
        app()->call([$secondJobRun, 'handle']);

        $this->assertDatabaseCount(DatabaseNotification::class, 2);
        $this->assertDatabaseHas(DatabaseNotification::class, [
            'notifiable_type' => User::class,
            'notifiable_id' => $followers->first()->id,
            'type' => NewPostNotification::class,
        ]);
        Event::assertNotDispatched(NewPostNotificationBroadcasted::class);
    }

    public function test_notification_job_broadcasts_when_socket_is_enabled(): void
    {
        config()->set('feed.notifications.socket_enabled', true);

        $author = User::factory()->create();
        $followers = User::factory()->count(2)->create();
        $post = Post::factory()->for($author, 'author')->create();

        foreach ($followers as $follower) {
            $this->follow($follower, $author);
        }

        Event::fake([NewPostNotificationBroadcasted::class]);

        $job = new NotifyFollowersOfNewPost($post->id);
        app()->call([$job, 'handle']);

        Event::assertDispatchedTimes(NewPostNotificationBroadcasted::class, 2);
        Event::assertDispatched(function (NewPostNotificationBroadcasted $event) use ($followers, $post): bool {
            return $event->userId === $followers->first()->id
                && $event->notification['post_id'] === $post->id
                && $event->notification['author_id'] === $post->user_id;
        });
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
