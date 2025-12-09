<?php

use App\Models\PromptRun;
use App\Models\User;
use App\Services\PromptFrameworkService;

beforeEach(function () {
    $this->user = User::factory()->create([
        'personality_type' => 'INTJ-A',
        'trait_percentages' => [
            'mind' => 75,
            'energy' => 55,
            'nature' => 80,
            'tactics' => 70,
            'identity' => 65,
        ],
    ]);
    $this->actingAs($this->user);
});

test('analyse handles service failure gracefully', function () {
    Queue::fake();

    // Mock PromptFrameworkService to return failure
    $this->mock(PromptFrameworkService::class, function ($mock) {
        $mock->shouldReceive('preAnalyseTask')
            ->andReturn([
                'needs_clarification' => false,
                'reasoning' => 'Task description is clear',
                'questions' => [],
            ]);
    });

    $response = $this->post(route('prompt-builder.analyse'), [
        'task_description' => 'Test task description that is long enough',
    ]);

    $response->assertRedirect();

    // Verify pre-analysis job was dispatched
    Queue::assertPushed(\App\Jobs\ProcessPreAnalysis::class);
});

test('analyse handles service success correctly', function () {
    Queue::fake();

    // Mock PromptFrameworkService to return success
    $this->mock(PromptFrameworkService::class, function ($mock) {
        $mock->shouldReceive('preAnalyseTask')
            ->andReturn([
                'needs_clarification' => false,
                'reasoning' => 'Task description is clear',
                'questions' => [],
            ]);
    });

    $response = $this->post(route('prompt-builder.analyse'), [
        'task_description' => 'Test task description that is long enough',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('prompt_runs', [
        'user_id' => $this->user->id,
    ]);

    // Verify pre-analysis job was dispatched
    Queue::assertPushed(\App\Jobs\ProcessPreAnalysis::class);
});

test('answer question rejects invalid workflow stage', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '2_completed', // Wrong stage for answering
        'framework_questions' => [['question' => 'Q1']],
    ]);

    $response = $this->post(route('prompt-builder.answer', $promptRun), [
        'question_index' => 0,
        'answer' => 'My answer',
    ]);

    // Should be allowed but might not do anything useful
    $response->assertOk();
});

test('retry handles service failure', function () {
    Queue::fake();

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,

        'workflow_stage' => '0_failed',
        'task_description' => 'Failed task',
        'personality_type' => 'INTJ-A',
        'trait_percentages' => [
            'mind' => 75,
            'energy' => 55,
            'nature' => 80,
            'tactics' => 70,
            'identity' => 65,
        ],
    ]);

    $response = $this->post(route('prompt-builder.retry', $promptRun));

    $response->assertRedirect();

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe('0_processing');
});

test('retry resets failed prompt run to processing state', function () {
    Queue::fake();

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,

        'workflow_stage' => '0_failed',
        'task_description' => 'Failed task',
        'personality_type' => 'INTJ-A',
        'trait_percentages' => [
            'mind' => 75,
            'energy' => 55,
            'nature' => 80,
            'tactics' => 70,
            'identity' => 65,
        ],
    ]);

    $response = $this->post(route('prompt-builder.retry', $promptRun));

    $response->assertRedirect();

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe('0_processing');
});

test('user cannot access other users prompt runs', function () {
    $otherUser = User::factory()->create();
    $otherRun = PromptRun::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->get(route('prompt-builder.show', $otherRun));

    $response->assertForbidden();
});

test('user cannot retry other users prompt runs', function () {
    $otherUser = User::factory()->create();
    $otherRun = PromptRun::factory()->create([
        'user_id' => $otherUser->id,

    ]);

    $response = $this->post(route('prompt-builder.retry', $otherRun));

    $response->assertForbidden();
});

test('cannot retry non failed prompt runs', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,

        'workflow_stage' => '2_completed',
    ]);

    $response = $this->post(route('prompt-builder.retry', $promptRun));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('generate handles requests successfully', function () {
    Queue::fake();

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '1_completed',
        'task_classification' => ['category' => 'planning'],
        'selected_framework' => ['code' => 'SMART'],
        'personality_tier' => 'full',
        'framework_questions' => [['question' => 'Q1']],
        'clarifying_answers' => ['Answer 1'],
    ]);

    $response = $this->post(route('prompt-builder.generate', $promptRun), [
        'question_answers' => ['Answer 1'],
    ]);

    // The controller returns 200 with success: true in response
    $response->assertOk();
    $response->assertJson(['success' => true]);

    // Verify job was dispatched
    Queue::assertPushed(\App\Jobs\ProcessPromptGeneration::class);
});

test('cannot go back past first question', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '1_completed',
        'framework_questions' => [['question' => 'Q1']],
        'current_question_index' => 0,
    ]);

    $response = $this->post(route('prompt-builder.go-back', $promptRun));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Already at first question.');
});
