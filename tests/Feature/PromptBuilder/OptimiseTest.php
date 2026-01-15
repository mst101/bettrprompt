<?php

use App\Models\PromptRun;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->withPersonality()->create();
});

test('generate optimised prompt successfully', function () {
    Queue::fake();
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '1_completed',
        'framework_questions' => [
            ['question' => 'Question 1'],
            ['question' => 'Question 2'],
        ],
        'clarifying_answers' => ['Answer 1', 'Answer 2'],
        'current_question_index' => 2,
        'task_classification' => ['category' => 'planning'],
        'selected_framework' => ['code' => 'SMART'],
        'personality_tier' => 'full',
    ]);

    $response = $this->post($this->countryRoute('prompt-builder.generate', [
        'promptRun' => $promptRun,
    ], absolute: false), [
        'question_answers' => ['Answer 1', 'Answer 2'],
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    // Verify workflow_stage was set to processing
    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe('2_processing')
        ->and($promptRun->clarifying_answers)->toBe(['Answer 1', 'Answer 2']);

    // Verify job was dispatched
    Queue::assertPushed(\App\Jobs\ProcessPromptGeneration::class);
});

test('update optimised prompt successfully', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '2_completed',
        'optimized_prompt' => 'Original prompt',
    ]);

    $response = $this->patch($this->countryRoute('prompt-builder.update-prompt', [
        'promptRun' => $promptRun,
    ], absolute: false), [
        'optimized_prompt' => 'Updated prompt text',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Prompt updated successfully.');

    $promptRun->refresh();
    expect($promptRun->optimized_prompt)->toBe('Updated prompt text');
});

test('update optimised prompt validates required field', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '2_completed',
    ]);

    $response = $this->patch($this->countryRoute('prompt-builder.update-prompt', [
        'promptRun' => $promptRun,
    ], absolute: false), []);

    $response->assertSessionHasErrors(['optimized_prompt']);
});

test('update optimised prompt validates string type', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '2_completed',
    ]);

    $response = $this->patch($this->countryRoute('prompt-builder.update-prompt', [
        'promptRun' => $promptRun,
    ], absolute: false), [
        'optimized_prompt' => ['not', 'a', 'string'],
    ]);

    $response->assertSessionHasErrors(['optimized_prompt']);
});

test('update optimised prompt validates max length', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '2_completed',
    ]);

    $response = $this->patch($this->countryRoute('prompt-builder.update-prompt', [
        'promptRun' => $promptRun,
    ], absolute: false), [
        'optimized_prompt' => str_repeat('a', 50001), // Exceeds max
    ]);

    $response->assertSessionHasErrors(['optimized_prompt']);
});

test('update optimised prompt only allows completed workflow stage', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '1_completed',
        'optimized_prompt' => null,
    ]);

    $response = $this->patch($this->countryRoute('prompt-builder.update-prompt', [
        'promptRun' => $promptRun,
    ], absolute: false), [
        'optimized_prompt' => 'Trying to update',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Can only edit completed prompt runs.');
});

test('update optimised prompt rejects failed prompt runs', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '2_failed',
        'optimized_prompt' => 'Original prompt',
    ]);

    $response = $this->patch($this->countryRoute('prompt-builder.update-prompt', [
        'promptRun' => $promptRun,
    ], absolute: false), [
        'optimized_prompt' => 'Updated prompt',
    ]);

    // Failed runs redirect with error session
    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('user cannot update other users prompt runs', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherRun = PromptRun::factory()->create([
        'user_id' => $otherUser->id,
        'workflow_stage' => '2_completed',
        'optimized_prompt' => 'Original prompt',
    ]);

    $response = $this->patch($this->countryRoute('prompt-builder.update-prompt', [
        'promptRun' => $otherRun,
    ], absolute: false), [
        'optimized_prompt' => 'Updated prompt',
    ]);

    $response->assertForbidden();
});

test('update optimised prompt preserves other fields', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '2_completed',
        'optimized_prompt' => 'Original prompt',
        'task_description' => 'Original task',
        'selected_framework' => ['code' => 'SMART'],
    ]);

    $this->patch($this->countryRoute('prompt-builder.update-prompt', [
        'promptRun' => $promptRun,
    ], absolute: false), [
        'optimized_prompt' => 'Updated prompt',
    ]);

    $promptRun->refresh();
    expect($promptRun->task_description)->toBe('Original task')
        ->and($promptRun->selected_framework)->toBe(['code' => 'SMART']);
});

test('update optimised prompt supports unicode characters', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '2_completed',
    ]);

    $unicodePrompt = 'Unicode test: 你好世界 🌍 Ñoño Über';

    $response = $this->patch($this->countryRoute('prompt-builder.update-prompt', [
        'promptRun' => $promptRun,
    ], absolute: false), [
        'optimized_prompt' => $unicodePrompt,
    ]);

    $response->assertRedirect();

    $promptRun->refresh();
    expect($promptRun->optimized_prompt)->toBe($unicodePrompt);
});

test('update optimised prompt allows newlines and formatting', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '2_completed',
    ]);

    $formattedPrompt = "Line 1\n\nLine 2\n\n- Bullet 1\n- Bullet 2";

    $response = $this->patch($this->countryRoute('prompt-builder.update-prompt', [
        'promptRun' => $promptRun,
    ], absolute: false), [
        'optimized_prompt' => $formattedPrompt,
    ]);

    $response->assertRedirect();

    $promptRun->refresh();
    expect($promptRun->optimized_prompt)->toBe($formattedPrompt);
});

test('update optimised prompt requires authentication or ownership', function () {
    // Test unauthenticated access to unowned prompt
    $promptRun = PromptRun::factory()->create([
        'workflow_stage' => '2_completed',
    ]);

    $response = $this->patchCountry(route('prompt-builder.update-prompt', [
        'promptRun' => $promptRun,
    ], absolute: false), [
        'optimized_prompt' => 'Updated prompt',
    ]);

    // Unauthenticated access to unowned prompt returns Forbidden
    $response->assertForbidden();
});
