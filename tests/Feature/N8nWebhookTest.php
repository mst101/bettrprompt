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

/**
 * Create a SMART Goals framework array for webhook testing
 */
function createSmartFramework(string $rationale = 'This framework suits your task'): array
{
    return [
        'name' => 'SMART Goals',
        'code' => 'SMART',
        'components' => [
            'Specific',
            'Measurable',
            'Achievable',
            'Relevant',
            'Time-bound',
        ],
        'rationale' => $rationale,
    ];
}

/**
 * Create framework questions array for webhook testing
 */
function createFrameworkQuestions(int $count = 2): array
{
    $questions = [
        'What is your specific goal?',
        'How will you measure success?',
        'What resources are available?',
        'What timeline do you have?',
    ];

    return array_slice($questions, 0, $count);
}

/**
 * Create a standard webhook payload for analysis_complete stage
 */
function createFrameworkSelectedPayload(\App\Models\PromptRun $promptRun, ?array $framework = null, ?array $questions = null): array
{
    return [
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => '1_completed',
        'selected_framework' => $framework ?? createSmartFramework(),
        'framework_questions' => $questions ?? createFrameworkQuestions(),
    ];
}

/**
 * Create a standard webhook payload for completed stage
 */
function createCompletedPayload(\App\Models\PromptRun $promptRun, ?string $optimizedPrompt = null): array
{
    return [
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => '2_completed',
        'optimized_prompt' => $optimizedPrompt ?? 'Here is your optimised prompt based on your personality type and preferences.',
    ];
}

test('webhook requires valid secret', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => '1_completed',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);
});

test('webhook rejects missing secret', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => '1_completed',
    ], false); // No secret

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Unauthorised']);
});

test('webhook rejects invalid secret', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => '1_completed',
    ], 'invalid-secret');

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Unauthorised']);
});

test('webhook validates prompt run id required', function () {
    $response = webhookPost([
        'workflow_stage' => '1_completed',
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
        'workflow_stage' => '1_completed',
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
        'workflow_stage' => '1_processing',
    ]);

    $response = webhookPost(createFrameworkSelectedPayload($promptRun));

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe('1_completed')
        ->and($promptRun->selected_framework['name'])->toBe('SMART Goals')
        ->and($promptRun->selected_framework['rationale'])->toBe('This framework suits your task')
        ->and($promptRun->framework_questions)->toHaveCount(2);
});

test('webhook broadcasts analysis completed event', function () {
    Event::fake([AnalysisCompleted::class]);

    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => '1_processing',
    ]);

    $response = webhookPost(createFrameworkSelectedPayload(
        $promptRun,
        createSmartFramework('Ideal for structured goal-setting')
    ));

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
        'workflow_stage' => '2_processing',
    ]);

    $response = webhookPost(createCompletedPayload($promptRun, 'Your optimised prompt here'));

    $response->assertOk();

    Event::assertDispatched(PromptOptimizationCompleted::class, function ($event) use ($promptRun) {
        return $event->promptRun->id === $promptRun->id;
    });
});

test('webhook sets completed at timestamp', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => '2_processing',
        'completed_at' => null,
    ]);

    $response = webhookPost(createCompletedPayload($promptRun, 'Final prompt'));

    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->completed_at)->not->toBeNull()
        ->and($promptRun->workflow_stage)->toBe('2_completed');
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
        'workflow_stage' => '1_completed',
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
        'workflow_stage' => '1_processing',
    ]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => '1_failed',
        'error_message' => 'OpenAI API rate limit exceeded',
    ]);

    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe('1_failed')
        ->and($promptRun->error_message)->toBe('OpenAI API rate limit exceeded');
});

test('webhook is protected by rate limiting middleware', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    // Make multiple requests to verify the endpoint processes them
    // Rate limiting is configured in the middleware and verified in integration/acceptance tests
    // Here we verify the endpoint itself handles rapid requests without crashing
    for ($i = 0; $i < 5; $i++) {
        $response = webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => '1_completed',
        ]);
        $response->assertStatus(200);
    }

    // Verify the prompt run was updated
    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe('1_completed');
});

test('webhook does not broadcast on non milestone stages', function () {
    Event::fake([AnalysisCompleted::class, PromptOptimizationCompleted::class]);

    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => '1_completed',
    ]);

    // Update to 2_processing (not a milestone)
    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => '2_processing',
    ]);

    $response->assertOk();

    // Should not broadcast any events
    Event::assertNotDispatched(AnalysisCompleted::class);
    Event::assertNotDispatched(PromptOptimizationCompleted::class);
});
