<?php

namespace Database\Seeders;

use App\Models\AlertRule;
use Illuminate\Database\Seeder;

class AlertSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AlertRule::create([
            'slug' => 'workflow_failure',
            'name' => 'Workflow Failure Alert',
            'alert_type' => 'workflow_failure',
            'conditions' => [
                'workflow_stages' => [0, 1, 2],
                'statuses' => ['failed', 'timeout'],
            ],
            'email_enabled' => true,
            'email_recipients' => 'hello@bettrprompt.ai',
            'in_app_enabled' => true,
            'debounce_minutes' => 15,
            'is_active' => true,
        ]);
    }
}
