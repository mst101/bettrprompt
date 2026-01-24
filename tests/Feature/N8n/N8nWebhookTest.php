<?php

use App\Enums\WorkflowStage;
use App\Events\AnalysisCompleted;
use App\Events\PromptOptimizationCompleted;
use App\Models\PromptRun;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->validSecret = setupN8nWebhookAuth();
});

test('webhook requires valid secret', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisCompleted,
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);
});

test('webhook rejects missing secret', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisCompleted,
    ], false); // No secret

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Unauthorized']);
});

test('webhook rejects invalid secret', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisCompleted,
    ], 'invalid-secret');

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Unauthorized']);
});

test('webhook validates prompt run id required', function () {
    $response = webhookPost([
        'workflow_stage' => WorkflowStage::AnalysisCompleted,
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
        'workflow_stage' => WorkflowStage::AnalysisCompleted,
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
        'workflow_stage' => WorkflowStage::AnalysisProcessing,
    ]);

    $response = webhookPost(createFrameworkSelectedPayload($promptRun));

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::AnalysisCompleted)
        ->and($promptRun->selected_framework['name'])->toBe('SMART Goals')
        ->and($promptRun->selected_framework['rationale'])->toBe('This framework suits your task')
        ->and($promptRun->framework_questions)->toHaveCount(2);
});

test('webhook broadcasts analysis completed event', function () {
    Event::fake([AnalysisCompleted::class]);

    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => WorkflowStage::AnalysisProcessing,
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
        'workflow_stage' => WorkflowStage::GenerationProcessing,
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
        'workflow_stage' => WorkflowStage::GenerationProcessing,
        'completed_at' => null,
    ]);

    $response = webhookPost(createCompletedPayload($promptRun, 'Final prompt'));

    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->completed_at)->not->toBeNull()
        ->and($promptRun->workflow_stage)->toBe(WorkflowStage::GenerationCompleted);
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
        'workflow_stage' => WorkflowStage::AnalysisCompleted,
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
        'workflow_stage' => WorkflowStage::AnalysisProcessing,
    ]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisFailed,
        'error_message' => 'OpenAI API rate limit exceeded',
    ]);

    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::AnalysisFailed)
        ->and($promptRun->error_message)->toBe('OpenAI API rate limit exceeded');
});

test('webhook handles rapid requests without error', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    // Make multiple requests to verify the endpoint processes them
    // Rate limiting is configured in the middleware and verified in integration/acceptance tests
    // Here we verify the endpoint itself handles rapid requests without crashing
    for ($i = 0; $i < 5; $i++) {
        $response = webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => WorkflowStage::AnalysisCompleted,
        ]);
        $response->assertStatus(200);
    }

    // Verify the prompt run was updated
    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::AnalysisCompleted);
});

test('webhook does not broadcast on non milestone stages', function () {
    Event::fake([AnalysisCompleted::class, PromptOptimizationCompleted::class]);

    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => WorkflowStage::AnalysisCompleted,
    ]);

    // Update to 2_processing (not a milestone)
    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::GenerationProcessing,
    ]);

    $response->assertOk();

    // Should not broadcast any events
    Event::assertNotDispatched(AnalysisCompleted::class);
    Event::assertNotDispatched(PromptOptimizationCompleted::class);
});
