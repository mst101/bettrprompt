<?php

namespace Database\Factories;

use App\Models\AnalyticsSession;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AnalyticsSessionFactory extends Factory
{
    protected $model = AnalyticsSession::class;

    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeThisMonth();

        return [
            'id' => Str::uuid(),
            'visitor_id' => Visitor::factory(),
            'user_id' => null,
            'started_at' => $startedAt,
            'ended_at' => $this->faker->dateTimeInInterval($startedAt, '+1 hour'),
            'duration_seconds' => $this->faker->numberBetween(30, 3600),
            'entry_page' => $this->faker->url(),
            'exit_page' => $this->faker->url(),
            'page_count' => $this->faker->numberBetween(1, 20),
            'event_count' => $this->faker->numberBetween(1, 50),
            'utm_source' => $this->faker->randomElement(['google', 'facebook', 'twitter', null]),
            'utm_medium' => $this->faker->randomElement(['cpc', 'organic', null]),
            'utm_campaign' => $this->faker->randomElement(['summer_sale', 'new_product', null]),
            'utm_term' => $this->faker->randomElement(['analytics', 'prompts', null]),
            'utm_content' => $this->faker->randomElement(['ad_variant_1', 'ad_variant_2', null]),
            'referrer' => $this->faker->randomElement(['google.com', 'facebook.com', null]),
            'device_type' => $this->faker->randomElement(['desktop', 'mobile', 'tablet']),
            'is_bounce' => $this->faker->boolean(20),
            'converted' => $this->faker->boolean(30),
            'conversion_type' => $this->faker->randomElement(['signup', 'purchase', null]),
            'prompts_started' => $this->faker->numberBetween(0, 5),
            'prompts_completed' => $this->faker->numberBetween(0, 3),
        ];
    }

    public function withUser(User $user): self
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function withVisitor(Visitor $visitor): self
    {
        return $this->state(fn (array $attributes) => [
            'visitor_id' => $visitor->id,
        ]);
    }
}
