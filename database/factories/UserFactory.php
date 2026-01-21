<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
            'name' => \fake()->name(),
            'email' => \fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user has a personality type and trait percentages.
     */
    public function withPersonality(string $type = 'INTJ-A'): static
    {
        return $this->state(fn (array $attributes) => [
            'personality_type' => $type,
            'trait_percentages' => [
                'mind' => 75,
                'energy' => 55,
                'nature' => 80,
                'tactics' => 70,
                'identity' => 65,
            ],
        ]);
    }

    /**
     * Indicate that the user has authenticated via Google OAuth.
     */
    public function withGoogleAuth(?string $googleId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'google_id' => $googleId ?? \Illuminate\Support\Str::ulid(),
        ]);
    }

    /**
     * Indicate that the user is on the Starter tier.
     */
    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'starter',
            'monthly_prompt_count' => 0,
            'prompt_count_reset_at' => now(),
        ]);
    }

    /**
     * Indicate that the user is on the Pro tier.
     */
    public function pro(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'pro',
            'monthly_prompt_count' => 0,
            'prompt_count_reset_at' => now(),
        ]);
    }

    /**
     * Indicate that the user is on the Premium tier.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'premium',
            'monthly_prompt_count' => 0,
            'prompt_count_reset_at' => now(),
        ]);
    }

    /**
     * Indicate that the user is on the Free tier (default).
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'free',
            'monthly_prompt_count' => 0,
            'prompt_count_reset_at' => now(),
        ]);
    }
}
