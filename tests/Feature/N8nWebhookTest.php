<?php

use App\Events\AnalysisCompleted;
use App\Events\PromptOptimizationCompleted;
use App\Models\PromptRun;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    // Set up a valid webhook secret
    $this->validSecret = 'test-webhook-secret-123';
    config(['services.n8n.webhook_secret' => $this->validSecret]);
});

/** Helper function to make authenticated webhook requests */
function webhookPost(array $data, ?string $secret = null): \Illuminate\Testing\TestResponse
{
    $validSecret = test()->validSecret;
    $secret = $secret ?? $validSecret;

    if ($secret === false) {
        // No secret header
        return test()->postJson('/api/n8n/webhook', $data);
    }

    return test()
        ->withHeaders(['X-N8N-SECRET' => $secret])
        ->postJson('/api/n8n/webhook', $data);
}

test('webhook requires valid secret', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => 'framework_selected',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);
});

test('webhook rejects missing secret', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => 'framework_selected',
    ], false); // No secret

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Unauthorised']);
});

test('webhook rejects invalid secret', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => 'framework_selected',
    ], 'invalid-secret');

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Unauthorised']);
});

test('webhook validates prompt run id required', function () {
    $response = webhookPost([
        'workflow_stage' => 'framework_selected',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'error' => 'Invalid payload',
    ]);
    $response->assertJsonStructure(['details' => ['prompt_run_id']]);
});

test('webhook validates prompt run id exists', function () {
    $response = webhookPost([
        'prompt_run_id' => 999999,
        'workflow_stage' => 'framework_selected',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'error' => 'Invalid payload',
    ]);
    $response->assertJsonStructure(['details' => ['prompt_run_id']]);
});

test('webhook validates workflow stage values', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => 'invalid_stage',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'error' => 'Invalid payload',
    ]);
    $response->assertJsonStructure(['details' => ['workflow_stage']]);
});

test('webhook validates status values', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'status' => 'invalid_status',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'error' => 'Invalid payload',
    ]);
    $response->assertJsonStructure(['details' => ['status']]);
});

test('webhook validates framework questions array', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'framework_questions' => 'not an array',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'error' => 'Invalid payload',
    ]);
    $response->assertJsonStructure(['details' => ['framework_questions']]);
});

test('webhook updates prompt run successfully', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => 'submitted',
        'status' => 'processing',
    ]);

    $selectedFramework = [
        'name' => 'SMART Goals',
        'code' => 'SMART',
        'components' => [
            'Specific',
            'Measurable',
            'Achievable',
            'Relevant',
            'Time-bound',
        ],
        'rationale' => 'This framework suits your task',
    ];

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => 'framework_selected',
        'status' => 'processing',
        'selected_framework' => $selectedFramework,
        'framework_questions' => [
            'What is your specific goal?',
            'How will you measure success?',
        ],
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe('framework_selected')
        ->and($promptRun->status)->toBe('processing')
        ->and($promptRun->selected_framework)->toBe($selectedFramework)
        ->and($promptRun->selected_framework['rationale'])->toBe('This framework suits your task')
        ->and($promptRun->framework_questions)->toHaveCount(2);
});

test('webhook broadcasts analysis completed event', function () {
    Event::fake([AnalysisCompleted::class]);

    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => 'submitted',
    ]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => 'framework_selected',
        'selected_framework' => [
            'name' => 'SMART Goals',
            'code' => 'SMART',
            'components' => ['Specific', 'Measurable', 'Achievable', 'Relevant', 'Time-bound'],
            'rationale' => 'Ideal for structured goal-setting',
        ],
    ]);

    $response->assertOk();

    Event::assertDispatched(AnalysisCompleted::class, function ($event) use ($promptRun) {
        return $event->promptRun->id === $promptRun->id;
    });
});

test('webhook broadcasts completion event', function () {
    Event::fake([PromptOptimizationCompleted::class]);

    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => 'generating_prompt',
    ]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => 'completed',
        'status' => 'completed',
        'optimized_prompt' => 'Your optimised prompt here',
    ]);

    $response->assertOk();

    Event::assertDispatched(PromptOptimizationCompleted::class, function ($event) use ($promptRun) {
        return $event->promptRun->id === $promptRun->id;
    });
});

test('webhook sets completed at timestamp', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => 'generating_prompt',
        'completed_at' => null,
    ]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => 'completed',
        'status' => 'completed',
        'optimized_prompt' => 'Final prompt',
    ]);

    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->completed_at)->not->toBeNull()
        ->and($promptRun->workflow_stage)->toBe('completed');
});

test('webhook handles missing prompt run', function () {
    // Create a prompt run then delete it
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
    $promptRunId = $promptRun->id;
    $promptRun->delete();

    // Validation kicks in before manual checks, so deleted IDs return 422, not 404
    $response = webhookPost([
        'prompt_run_id' => $promptRunId,
        'workflow_stage' => 'framework_selected',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'error' => 'Invalid payload',
    ]);
    $response->assertJsonStructure(['details' => ['prompt_run_id']]);
});

test('webhook stores error message', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => 'submitted',
    ]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => 'failed',
        'status' => 'failed',
        'error_message' => 'OpenAI API rate limit exceeded',
    ]);

    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe('failed')
        ->and($promptRun->status)->toBe('failed')
        ->and($promptRun->error_message)->toBe('OpenAI API rate limit exceeded');
});

test('webhook respects rate limiting', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    // Make 61 requests (limit is 60 per minute)
    for ($i = 0; $i < 61; $i++) {
        $response = webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => 'framework_selected',
        ]);

        if ($i < 60) {
            $response->assertStatus(200);
        } else {
            // 61st request should be rate limited
            $response->assertStatus(429);
        }
    }
});

test('webhook does not broadcast on non milestone stages', function () {
    Event::fake([AnalysisCompleted::class, PromptOptimizationCompleted::class]);

    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => 'framework_selected',
    ]);

    // Update to answering_questions (not a milestone)
    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => 'answering_questions',
    ]);

    $response->assertOk();

    // Should not broadcast any events
    Event::assertNotDispatched(AnalysisCompleted::class);
    Event::assertNotDispatched(PromptOptimizationCompleted::class);
});
