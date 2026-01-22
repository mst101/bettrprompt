<?php

namespace Database\Factories;

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AnalyticsEventFactory extends Factory
{
    protected $model = AnalyticsEvent::class;

    public function definition(): array
    {
        return [
            'event_id' => Str::uuid(),
            'name' => $this->faker->randomElement(['page_view', 'prompt_started', 'prompt_completed']),
            'type' => $this->faker->randomElement(['engagement', 'conversion', 'exposure']),
            'properties' => json_encode([
                'path' => $this->faker->url(),
            ]),
            'visitor_id' => null,
            'user_id' => null,
            'session_id' => AnalyticsSession::factory(),
            'source' => 'client',
            'page_path' => $this->faker->url(),
            'referrer' => $this->faker->url(),
            'device_type' => $this->faker->randomElement(['desktop', 'mobile', 'tablet']),
            'occurred_at' => $this->faker->dateTimeThisMonth(),
        ];
    }
}
