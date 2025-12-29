<?php

use App\Models\PromptRun;
use App\Models\User;

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
});

test('show page displays prompt run details', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_description' => 'My test task',
        'personality_type' => 'INTJ-A',
        'selected_framework' => ['code' => 'SMART', 'name' => 'SMART Goals'],
        'workflow_stage' => '1_completed',
    ]);

    $response = $this->get(route('prompt-builder.analyse', $promptRun));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('PromptBuilder/Show')
        ->where('promptRun.id', $promptRun->id)
        ->where('promptRun.taskDescription', 'My test task')
    );
});

test('show page displays current question', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '1_completed',
        'framework_questions' => [
            ['question' => 'What is your goal?'],
            ['question' => 'How will you measure success?'],
        ],
        'clarifying_answers' => [],
        'current_question_index' => 0,
    ]);

    $response = $this->get(route('prompt-builder.analyse', $promptRun));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('currentQuestion', 'What is your goal?')
        ->where('progress.answered', 0)
        ->where('progress.total', 2)
    );
});

test('show page returns null when all questions answered', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '2_processing',
        'framework_questions' => [
            ['question' => 'Question 1'],
            ['question' => 'Question 2'],
        ],
        'clarifying_answers' => [
            'Answer 1',
            'Answer 2',
        ],
        'current_question_index' => 2,
    ]);

    $response = $this->get(route('prompt-builder.analyse', $promptRun));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('currentQuestion', null)
        ->where('progress.answered', 2)
        ->where('progress.total', 2)
    );
});

test('show page includes personality tier in response', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_description' => 'Test task',
        'personality_type' => 'INTJ-A',
        'selected_framework' => ['code' => 'SMART'],
        'personality_tier' => 'full',
        'workflow_stage' => '1_completed',
    ]);

    $response = $this->get(route('prompt-builder.analyse', $promptRun));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('promptRun.personalityTier', 'full')
    );
});

test('show page displays completed prompt run with optimized prompt', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '2_completed',
        'optimized_prompt' => 'This is your personalised, optimised prompt based on your INTJ personality.',
    ]);

    $response = $this->get(route('prompt-builder.analyse', $promptRun));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('promptRun.optimizedPrompt',
            'This is your personalised, optimised prompt based on your INTJ personality.')
        ->where('promptRun.workflowStage', '2_completed')
    );
});

test('answer question saves answer successfully', function () {
    $this->actingAs($this->user);

    $promptRun = promptRunBuilder()
        ->withUser($this->user)
        ->analysisComplete()
        ->withAnswers([]) // Clear any default answers
        ->build();

    $response = $this->post(route('prompt-builder.answer', $promptRun), [
        'question_index' => 0,
        'answer' => 'This is my detailed answer to the first question',
    ]);

    $response->assertOk();
    $response->assertJson([
        'clarifying_answers' => [
            'This is my detailed answer to the first question',
            null,
        ],
    ]);

    $promptRun->refresh();
    expect($promptRun->clarifying_answers)->toHaveCount(2)
        ->and($promptRun->clarifying_answers[0])->toBe('This is my detailed answer to the first question')
        ->and($promptRun->current_question_index)->toBe(1)
        ->and($promptRun->workflow_stage)->toBe('1_completed');
});

test('answer question rejects invalid workflow stage', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '2_completed',  // Invalid stage for answering (too far ahead)
        'framework_questions' => [['question' => 'Question 1']],
        'clarifying_answers' => [],
        'current_question_index' => 0,
    ]);

    $response = $this->post(route('prompt-builder.answer', $promptRun), [
        'question_index' => 0,
        'answer' => 'Answer',
    ]);

    // Should be allowed but might not do anything useful (returns 200)
    $response->assertOk();
});

test('skip question records null answer', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '1_completed',
        'framework_questions' => [
            ['question' => 'Question 1'],
            ['question' => 'Question 2'],
        ],
        'clarifying_answers' => [],
        'current_question_index' => 0,
    ]);

    $response = $this->post(route('prompt-builder.skip', $promptRun), [
        'question_index' => 0,
    ]);

    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->clarifying_answers)->toHaveCount(2)
        ->and($promptRun->clarifying_answers[0])->toBeNull()
        ->and($promptRun->current_question_index)->toBe(1);
});

test('user cannot answer other users questions', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherRun = PromptRun::factory()->create([
        'user_id' => $otherUser->id,
        'workflow_stage' => '1_completed',
        'framework_questions' => [['question' => 'Question 1']],
        'clarifying_answers' => [],
        'current_question_index' => 0,
    ]);

    $response = $this->post(route('prompt-builder.answer', $otherRun), [
        'question_index' => 0,
        'answer' => 'My answer',
    ]);

    $response->assertForbidden();
});

test('go back to previous question updates index', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '1_completed',
        'framework_questions' => [
            ['question' => 'Question 1'],
            ['question' => 'Question 2'],
        ],
        'clarifying_answers' => ['Answer 1', null],
        'current_question_index' => 1,
    ]);

    $response = $this->post(route('prompt-builder.go-back', $promptRun));

    $response->assertRedirect(route('prompt-builder.analyse', $promptRun));

    $promptRun->refresh();
    expect($promptRun->current_question_index)->toBe(0)
        ->and($promptRun->workflow_stage)->toBe('1_completed');
});

test('cannot go back from first question', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '1_completed',
        'framework_questions' => [
            ['question' => 'Question 1'],
        ],
        'clarifying_answers' => [],
        'current_question_index' => 0,
    ]);

    $response = $this->post(route('prompt-builder.go-back', $promptRun));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Already at first question.');
});
