<?php

namespace Database\Factories;

use App\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Visitor>
 */
class VisitorFactory extends Factory
{
    protected $model = Visitor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'utm_source' => fake()->optional()->randomElement(['google', 'facebook', 'twitter', 'email']),
            'utm_medium' => fake()->optional()->randomElement(['cpc', 'organic', 'social', 'email']),
            'utm_campaign' => fake()->optional()->word(),
            'utm_term' => fake()->optional()->words(2, true),
            'utm_content' => fake()->optional()->word(),
            'referrer' => fake()->optional()->url(),
            'landing_page' => fake()->url(),
            'user_agent' => fake()->userAgent(),
            'ip_address' => fake()->ipv4(),
            'first_visit_at' => $firstVisit = now()->subDays(fake()->numberBetween(0, 30)),
            'last_visit_at' => fake()->dateTimeBetween($firstVisit, 'now'),
        ];
    }

    /**
     * Indicate that this is a first-time visitor.
     */
    public function firstVisit(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_visit_at' => now(),
            'last_visit_at' => now(),
        ]);
    }

    /**
     * Indicate that this visitor came from a specific campaign.
     */
    public function fromCampaign(string $source, string $medium, string $campaign): static
    {
        return $this->state(fn (array $attributes) => [
            'utm_source' => $source,
            'utm_medium' => $medium,
            'utm_campaign' => $campaign,
        ]);
    }
}
