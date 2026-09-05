<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content' => $this->faker->sentence(16),
            'likes_count' => 0,
        ];
    }

    public function featured(): static
    {
        return $this->state([
            'content' => 'A fast-moving regional story is gathering a huge response across the network.',
        ]);
    }
}
