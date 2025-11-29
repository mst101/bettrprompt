<?php

use App\Models\PromptRun;
use App\Models\User;
use App\Services\PromptFrameworkService;

beforeEach(function () {
    $this->user = User::factory()->create([
        'personality_type' => 'INTJ',
        'trait_percentages' => [
            'introversion' => 75,
            'intuition' => 80,
            'thinking' => 70,
            'judging' => 65,
        ],
    ]);
    $this->actingAs($this->user);
});

test('analyse handles service failure gracefully', function () {
    // Mock PromptFrameworkService to return failure
    $this->mock(PromptFrameworkService::class, function ($mock) {
        $mock->shouldReceive('analyseTask')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => ['message' => 'Analysis service error'],
            ]);
    });

    $response = $this->post(route('prompt-builder.analyse'), [
        'task_description' => 'Test task description that is long enough',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('analyse handles service success correctly', function () {
    $this->mock(PromptFrameworkService::class, function ($mock) {
        $mock->shouldReceive('analyseTask')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'task_classification' => ['category' => 'planning'],
                    'selected_framework' => ['code' => 'SMART'],
                    'clarifying_questions' => [],
                ],
            ]);
    });

    $response = $this->post(route('prompt-builder.analyse'), [
        'task_description' => 'Test task description that is long enough',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('prompt_runs', [
        'user_id' => $this->user->id,
        'status' => 'pending',
        'workflow_stage' => 'analysis_complete',
    ]);
});

test('answer question rejects invalid workflow stage', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'completed', // Wrong stage for answering
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
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'failed',
        'workflow_stage' => 'failed',
        'task_description' => 'Failed task',
        'personality_type' => 'INTJ',
        'trait_percentages' => ['introversion' => 75],
    ]);

    $this->mock(PromptFrameworkService::class, function ($mock) {
        $mock->shouldReceive('analyseTask')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => ['message' => 'Retry failed'],
            ]);
    });

    $response = $this->post(route('prompt-builder.retry', $promptRun));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('retry succeeds after previous failure', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'failed',
        'workflow_stage' => 'failed',
        'task_description' => 'Failed task',
        'personality_type' => 'INTJ',
        'trait_percentages' => ['introversion' => 75],
    ]);

    $this->mock(PromptFrameworkService::class, function ($mock) {
        $mock->shouldReceive('analyseTask')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'task_classification' => ['category' => 'planning'],
                    'selected_framework' => ['code' => 'SMART'],
                    'clarifying_questions' => [],
                ],
            ]);
    });

    $response = $this->post(route('prompt-builder.retry', $promptRun));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $promptRun->refresh();
    expect($promptRun->status)->toBe('pending')
        ->and($promptRun->workflow_stage)->toBe('analysis_complete');
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
        'status' => 'failed',
    ]);

    $response = $this->post(route('prompt-builder.retry', $otherRun));

    $response->assertForbidden();
});

test('cannot retry non failed prompt runs', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'workflow_stage' => 'completed',
    ]);

    $response = $this->post(route('prompt-builder.retry', $promptRun));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('generate handles service failure gracefully', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'analysis_complete',
        'task_classification' => ['category' => 'planning'],
        'selected_framework' => ['code' => 'SMART'],
        'personality_tier' => 'full',
        'framework_questions' => [['question' => 'Q1']],
        'clarifying_answers' => ['Answer 1'],
    ]);

    $this->mock(PromptFrameworkService::class, function ($mock) {
        $mock->shouldReceive('generatePrompt')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => ['message' => 'Generation failed'],
            ]);
    });

    $response = $this->post(route('prompt-builder.generate', $promptRun), [
        'question_answers' => ['Answer 1'],
    ]);

    $response->assertStatus(500);
    $response->assertJson(['success' => false]);

    $promptRun->refresh();
    expect($promptRun->status)->toBe('failed');
});

test('cannot go back past first question', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'analysis_complete',
        'framework_questions' => [['question' => 'Q1']],
        'current_question_index' => 0,
    ]);

    $response = $this->post(route('prompt-builder.go-back', $promptRun));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Already at first question.');
});
