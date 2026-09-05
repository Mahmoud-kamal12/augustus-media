<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
        ];
    }

    public function demoAccount(): static
    {
        return $this->state([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);
    }

    public function newsAccount(): static
    {
        return $this->state([
            'name' => 'Augustus News',
            'email' => 'augustus-news@example.com',
        ]);
    }
}
