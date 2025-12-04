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
});

test('create child with new task description successfully', function () {
    $this->actingAs($this->user);

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'status' => 'completed',
        'workflow_stage' => 'completed',
    ]);

    $response = $this->post(route('prompt-builder.create-child', $parentRun), [
        'task_description' => 'Updated task description for child run',
    ]);

    $response->assertRedirect();

    // Verify child was created
    $childRun = PromptRun::where('parent_id', $parentRun->id)->first();
    expect($childRun)->not->toBeNull()
        ->and($childRun->task_description)->toBe('Updated task description for child run')
        ->and($childRun->parent_id)->toBe($parentRun->id)
        ->and($childRun->status)->toBeIn(['processing', 'pending'])
        ->and($childRun->workflow_stage)->toBeIn(['submitted', 'analysis_complete']);
});

test('create child validates task description required', function () {
    $this->actingAs($this->user);

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->post(route('prompt-builder.create-child', $parentRun), []);

    $response->assertSessionHasErrors(['task_description']);
});

test('user cannot create child of other users prompt run', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherRun = PromptRun::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->post(route('prompt-builder.create-child', $otherRun), [
        'task_description' => 'New task description',
    ]);

    $response->assertForbidden();
});

test('create child from edited answers successfully', function () {
    $this->actingAs($this->user);

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'selected_framework' => ['code' => 'SMART'],
        'framework_questions' => [
            ['question' => 'Question 1'],
            ['question' => 'Question 2'],
        ],
        'clarifying_answers' => ['Old answer 1', 'Old answer 2'],
        'personality_tier' => 'full',
        'status' => 'completed',
        'workflow_stage' => 'completed',
    ]);

    // Mock PromptFrameworkService
    $this->mock(PromptFrameworkService::class, function ($mock) {
        $mock->shouldReceive('generatePrompt')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'optimised_prompt' => 'New optimised prompt',
                    'framework_used' => ['code' => 'SMART'],
                ],
            ]);
    });

    $response = $this->post(route('prompt-builder.create-child-from-answers', $parentRun), [
        'clarifying_answers' => ['New answer 1', 'New answer 2'],
    ]);

    $response->assertRedirect();

    // Verify child was created
    $childRun = PromptRun::where('parent_id', $parentRun->id)->first();
    expect($childRun)->not->toBeNull()
        ->and($childRun->clarifying_answers)->toBe(['New answer 1', 'New answer 2'])
        ->and($childRun->parent_id)->toBe($parentRun->id);
});

test('create child from answers validates clarifying answers required', function () {
    $this->actingAs($this->user);

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'framework_questions' => [['question' => 'Question 1']],
    ]);

    $response = $this->post(route('prompt-builder.create-child-from-answers', $parentRun), []);

    $response->assertSessionHasErrors(['clarifying_answers']);
});

test('create child from answers converts empty strings to null', function () {
    $this->actingAs($this->user);

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'selected_framework' => ['code' => 'SMART'],
        'framework_questions' => [
            ['question' => 'Question 1'],
            ['question' => 'Question 2'],
        ],
        'personality_tier' => 'full',
        'status' => 'completed',
    ]);

    $this->mock(PromptFrameworkService::class, function ($mock) {
        $mock->shouldReceive('generatePrompt')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'optimised_prompt' => 'Prompt',
                    'framework_used' => ['code' => 'SMART'],
                ],
            ]);
    });

    $response = $this->post(route('prompt-builder.create-child-from-answers', $parentRun), [
        'clarifying_answers' => ['Answer 1', ''], // Empty string should become null
    ]);

    $response->assertRedirect();

    $childRun = PromptRun::where('parent_id', $parentRun->id)->first();
    expect($childRun->clarifying_answers)->toBe(['Answer 1', null]);
});

test('user cannot create child from answers of other users prompt run', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherRun = PromptRun::factory()->create([
        'user_id' => $otherUser->id,
        'framework_questions' => [['question' => 'Question 1']],
    ]);

    $response = $this->post(route('prompt-builder.create-child-from-answers', $otherRun), [
        'clarifying_answers' => ['Answer'],
    ]);

    $response->assertForbidden();
});

test('create child from answers rejects parent without framework questions', function () {
    $this->actingAs($this->user);

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'framework_questions' => [], // No questions
    ]);

    $response = $this->post(route('prompt-builder.create-child-from-answers', $parentRun), [
        'clarifying_answers' => ['Answer'],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('parent child relationship is correctly established', function () {
    $this->actingAs($this->user);

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'selected_framework' => ['code' => 'SMART'],
        'framework_questions' => [['question' => 'Q1']],
        'personality_tier' => 'full',
    ]);

    $this->mock(PromptFrameworkService::class, function ($mock) {
        $mock->shouldReceive('generatePrompt')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'optimised_prompt' => 'Prompt',
                    'framework_used' => ['code' => 'SMART'],
                ],
            ]);
    });

    $this->post(route('prompt-builder.create-child-from-answers', $parentRun), [
        'clarifying_answers' => ['Answer'],
    ]);

    $parentRun->refresh();
    $childRun = $parentRun->children()->first();

    expect($childRun->parent_id)->toBe($parentRun->id)
        ->and($childRun->parent->id)->toBe($parentRun->id)
        ->and($parentRun->children)->toHaveCount(1);
});

test('child prompt run inherits personality from user', function () {
    $this->actingAs($this->user);

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'personality_type' => 'ENFP', // Different from user
        'selected_framework' => ['code' => 'SMART'],
        'framework_questions' => [['question' => 'Q1']],
        'personality_tier' => 'full',
    ]);

    $this->mock(PromptFrameworkService::class, function ($mock) {
        $mock->shouldReceive('generatePrompt')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'optimised_prompt' => 'Prompt',
                    'framework_used' => ['code' => 'SMART'],
                ],
            ]);
    });

    $this->post(route('prompt-builder.create-child-from-answers', $parentRun), [
        'clarifying_answers' => ['Answer'],
    ]);

    $childRun = PromptRun::where('parent_id', $parentRun->id)->first();

    // Child should use current user's personality
    expect($childRun->personality_type)->toBe($this->user->personality_type)
        ->and($childRun->trait_percentages)->toBe($this->user->trait_percentages);
});
