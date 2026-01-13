<?php

namespace Database\Seeders;

use App\Models\Funnel;
use App\Models\FunnelStage;
use Illuminate\Database\Seeder;

class FunnelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $registrationFunnel = Funnel::create([
            'slug' => 'registration',
            'name' => 'Registration Funnel',
            'description' => 'Tracks user journey from visit to paid subscription',
            'is_active' => true,
            'attribution_window_days' => 30,
        ]);

        FunnelStage::create([
            'funnel_id' => $registrationFunnel->id,
            'order' => 1,
            'name' => 'Visit',
            'event_name' => 'page_view',
            'event_conditions' => [
                'first_occurrence' => true,
            ],
        ]);

        FunnelStage::create([
            'funnel_id' => $registrationFunnel->id,
            'order' => 2,
            'name' => 'First Prompt',
            'event_name' => 'prompt_completed',
            'event_conditions' => [
                'first_occurrence' => true,
            ],
        ]);

        FunnelStage::create([
            'funnel_id' => $registrationFunnel->id,
            'order' => 3,
            'name' => 'Sign Up',
            'event_name' => 'registration_completed',
            'event_conditions' => null,
        ]);

        FunnelStage::create([
            'funnel_id' => $registrationFunnel->id,
            'order' => 4,
            'name' => 'Subscription',
            'event_name' => 'subscription_success',
            'event_conditions' => [
                'tier_filter' => ['pro', 'private'],
            ],
        ]);
    }
}
