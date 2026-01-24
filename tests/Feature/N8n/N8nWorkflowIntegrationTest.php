<?php

/**
 * Comprehensive integration tests for n8n workflow scenarios
 *
 * These tests verify end-to-end workflow progression through multiple webhook updates
 */

use App\Enums\WorkflowStage;
use App\Events\AnalysisCompleted;
use App\Events\PromptOptimizationCompleted;
use App\Models\PromptRun;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->validSecret = setupN8nWebhookAuth();
});

test('complete workflow progression from submission to completion', function () {
    Event::fake();

    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => WorkflowStage::AnalysisProcessing,

    ]);

    // Step 1: Framework selected
    webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisCompleted,

        'selected_framework' => createSmartFramework(),
        'framework_questions' => createFrameworkQuestions(2),
    ]);

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::AnalysisCompleted)
        ->and($promptRun->selected_framework['code'])->toBe('SMART');

    // Step 2: User answers questions (simulated)
    $promptRun->update([
        'clarifying_answers' => ['Goal 1', 'Measure 1'],
        'current_question_index' => 2,
    ]);

    // Step 3: Prompt generation
    webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::GenerationProcessing,

    ]);

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::GenerationProcessing);

    // Step 4: Completion
    webhookPost(createCompletedPayload(
        $promptRun,
        'Your optimised prompt considering your SMART framework preferences...'
    ));

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::GenerationCompleted)
        ->and($promptRun->completed_at)->not->toBeNull()
        ->and($promptRun->optimized_prompt)->toContain('SMART framework');
});

test('workflow handles user personality during processing', function () {
    Event::fake();

    $user = User::factory()
        ->withPersonality('ENFP-A')
        ->create();

    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'personality_type' => 'ENFP-A',
        'workflow_stage' => WorkflowStage::AnalysisProcessing,
        'task_description' => 'Create a marketing campaign for a new product',
    ]);

    // Framework selected tailored to ENFP personality
    webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisCompleted,
        'selected_framework' => [
            'name' => 'Design Thinking',
            'code' => 'DT',
            'components' => ['Empathize', 'Define', 'Ideate', 'Prototype', 'Test'],
            'rationale' => 'Perfect for creative and people-focused ENFPs',
        ],
        'framework_questions' => [
            'Who is your target audience?',
            'What emotions do you want to evoke?',
            'How will you test your campaign?',
        ],
    ]);

    $promptRun->refresh();
    expect($promptRun->selected_framework['code'])->toBe('DT')
        ->and($promptRun->framework_questions)->toHaveCount(3)
        ->and($promptRun->personality_type)->toBe('ENFP-A');
});

test('workflow recovers from failed state', function () {
    Event::fake();

    $user = User::factory()->create();
    $promptRun = promptRunBuilder()
        ->withUser($user)
        ->failed('API timeout error')
        ->build();

    expect($promptRun->isFailed())->toBeTrue()
        ->and($promptRun->error_message)->toContain('timeout');

    // Retry: reset to processing
    webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisCompleted,

        'selected_framework' => createSmartFramework(),
        'error_message' => null,
    ]);

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::AnalysisCompleted)
        ->and($promptRun->error_message)->toBeNull();
});

test('workflow broadcasts events at correct milestones', function () {
    Event::fake();

    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => WorkflowStage::AnalysisProcessing,
    ]);

    // Framework selection triggers analysis completed event
    webhookPost(createFrameworkSelectedPayload($promptRun));
    Event::assertDispatched(AnalysisCompleted::class);

    // Completion triggers prompt optimization completed event
    webhookPost(createCompletedPayload($promptRun));
    Event::assertDispatched(PromptOptimizationCompleted::class);
});

test('workflow preserves user context through multiple updates', function () {
    $user = User::factory()
        ->withPersonality('ISTJ-T')
        ->create();

    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'task_description' => 'Design a project management system for a structured organization',
        'personality_type' => 'ISTJ-T',
        'trait_percentages' => [
            'mind' => 85,
            'energy' => 55,
            'nature' => 75,
            'tactics' => 90,
            'identity' => 60,
        ],
    ]);

    // Update workflow but verify user context persists
    webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisCompleted,
        'selected_framework' => [
            'name' => 'Waterfall',
            'code' => 'WF',
            'components' => ['Requirements', 'Design', 'Implementation', 'Testing', 'Deployment'],
            'rationale' => 'Best for structured, detail-oriented minds',
        ],
    ]);

    $promptRun->refresh();
    expect($promptRun->user_id)->toBe($user->id)
        ->and($promptRun->personality_type)->toBe('ISTJ-T')
        ->and($promptRun->trait_percentages['tactics'])->toBe(90)
        ->and($promptRun->task_description)->toContain('project management')
        ->and($promptRun->selected_framework['rationale'])->toContain('structured');
});

test('workflow handles multiple concurrent updates correctly', function () {
    Event::fake();

    $user = User::factory()->create();
    $promptRun1 = PromptRun::factory()->create(['user_id' => $user->id]);
    $promptRun2 = PromptRun::factory()->create(['user_id' => $user->id]);

    // Update first prompt run
    webhookPost(createFrameworkSelectedPayload($promptRun1));
    $promptRun1->refresh();
    expect($promptRun1->workflow_stage)->toBe(WorkflowStage::AnalysisCompleted);

    // Update second prompt run independently
    webhookPost(createCompletedPayload($promptRun2));
    $promptRun2->refresh();
    expect($promptRun2->workflow_stage)->toBe(WorkflowStage::GenerationCompleted);

    // First should not be affected
    $promptRun1->refresh();
    expect($promptRun1->workflow_stage)->toBe(WorkflowStage::AnalysisCompleted)
        ->and($promptRun1->id)->not->toBe($promptRun2->id);
});

test('workflow validates data integrity through updates', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'task_description' => 'Original task description',
    ]);

    $framework = createSmartFramework('Custom rationale');

    webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisCompleted,
        'selected_framework' => $framework,
        'framework_questions' => createFrameworkQuestions(3),
    ]);

    $promptRun->refresh();

    // Verify all data was saved correctly
    expect($promptRun->task_description)->toBe('Original task description')
        ->and($promptRun->selected_framework['rationale'])->toBe('Custom rationale')
        ->and($promptRun->selected_framework['code'])->toBe('SMART')
        ->and($promptRun->framework_questions)->toHaveCount(3);
});
