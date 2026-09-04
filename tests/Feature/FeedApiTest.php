<?php

namespace Tests\Feature;

use App\Jobs\NotifyFollowersOfNewPost;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

        $this->assertDatabaseCount('follows', 1);
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

        DB::table('likes')->insert([
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

        $this->assertDatabaseCount('likes', 1);

        $this->deleteJson("/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('data.is_liked', false)
            ->assertJsonStructure(['data' => ['likes_count']]);

        $this->deleteJson("/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('data.is_liked', false)
            ->assertJsonStructure(['data' => ['likes_count']]);

        $this->assertDatabaseCount('likes', 0);
    }

    public function test_post_show_returns_current_like_summary(): void
    {
        $viewer = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create();

        DB::table('likes')->insert([
            [
                'post_id' => $post->id,
                'user_id' => $viewer->id,
                'created_at' => now(),
            ],
            [
                'post_id' => $post->id,
                'user_id' => $otherUser->id,
                'created_at' => now(),
            ],
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson("/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 2)
            ->assertJsonPath('data.is_liked', true);
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

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
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

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_notification_job_writes_one_record_per_follower_idempotently(): void
    {
        $author = User::factory()->create();
        $followers = User::factory()->count(2)->create();
        $post = Post::factory()->for($author, 'author')->create();

        foreach ($followers as $follower) {
            $this->follow($follower, $author);
        }

        (new NotifyFollowersOfNewPost($post->id))->handle();
        (new NotifyFollowersOfNewPost($post->id))->handle();

        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $followers->first()->id,
            'type' => 'App\\Notifications\\NewPostNotification',
        ]);
    }

    private function follow(User $follower, User $author): void
    {
        DB::table('follows')->insert([
            'follower_id' => $follower->id,
            'followed_id' => $author->id,
            'created_at' => now(),
        ]);
    }
}
