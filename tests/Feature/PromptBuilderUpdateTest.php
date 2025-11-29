<?php

use App\Models\PromptRun;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'personality_type' => 'INTJ',
    ]);
    $this->actingAs($this->user);
});

test('update optimised prompt successfully', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'workflow_stage' => 'completed',
        'optimized_prompt' => 'Original prompt text',
    ]);

    $response = $this->patch(route('prompt-builder.update-prompt', $promptRun), [
        'optimized_prompt' => 'Updated prompt text',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $promptRun->refresh();
    expect($promptRun->optimized_prompt)->toBe('Updated prompt text');
});

test('update optimised prompt validates required field', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'workflow_stage' => 'completed',
    ]);

    $response = $this->patch(route('prompt-builder.update-prompt', $promptRun), []);

    $response->assertSessionHasErrors(['optimized_prompt']);
});

test('update optimised prompt validates string type', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'workflow_stage' => 'completed',
    ]);

    $response = $this->patch(route('prompt-builder.update-prompt', $promptRun), [
        'optimized_prompt' => ['not' => 'a string'], // Array instead of string
    ]);

    $response->assertSessionHasErrors(['optimized_prompt']);
});

test('update optimised prompt validates max length', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'workflow_stage' => 'completed',
    ]);

    $response = $this->patch(route('prompt-builder.update-prompt', $promptRun), [
        'optimized_prompt' => str_repeat('a', 50001), // Exceeds max of 50000
    ]);

    $response->assertSessionHasErrors(['optimized_prompt']);
});

test('update optimised prompt only allows completed workflow stage', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'pending',
        'workflow_stage' => 'answering_questions',
    ]);

    $response = $this->patch(route('prompt-builder.update-prompt', $promptRun), [
        'optimized_prompt' => 'New prompt',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('update optimised prompt rejects failed prompt runs', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'failed',
        'workflow_stage' => 'failed',
    ]);

    $response = $this->patch(route('prompt-builder.update-prompt', $promptRun), [
        'optimized_prompt' => 'New prompt',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('user cannot update other users prompt runs', function () {
    $otherUser = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $otherUser->id,
        'status' => 'completed',
        'workflow_stage' => 'completed',
    ]);

    $response = $this->patch(route('prompt-builder.update-prompt', $promptRun), [
        'optimized_prompt' => 'Hacked prompt',
    ]);

    $response->assertForbidden();
});

test('update optimised prompt preserves other fields', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'workflow_stage' => 'completed',
        'optimized_prompt' => 'Original',
        'task_description' => 'Original task',
        'personality_type' => 'INTJ',
    ]);

    $this->patch(route('prompt-builder.update-prompt', $promptRun), [
        'optimized_prompt' => 'Updated',
    ]);

    $promptRun->refresh();
    expect($promptRun->optimized_prompt)->toBe('Updated')
        ->and($promptRun->task_description)->toBe('Original task')
        ->and($promptRun->personality_type)->toBe('INTJ');
});

test('update optimised prompt supports unicode characters', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'workflow_stage' => 'completed',
    ]);

    $unicodeText = 'Hello 你好 مرحبا 🚀';
    $response = $this->patch(route('prompt-builder.update-prompt', $promptRun), [
        'optimized_prompt' => $unicodeText,
    ]);

    $response->assertRedirect();
    $promptRun->refresh();
    expect($promptRun->optimized_prompt)->toBe($unicodeText);
});

test('update optimised prompt allows newlines and formatting', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'workflow_stage' => 'completed',
    ]);

    $formattedText = "Line 1\n\nLine 2\n\tIndented line";
    $response = $this->patch(route('prompt-builder.update-prompt', $promptRun), [
        'optimized_prompt' => $formattedText,
    ]);

    $response->assertRedirect();
    $promptRun->refresh();
    expect($promptRun->optimized_prompt)->toBe($formattedText);
});

test('update optimised prompt requires authentication or ownership', function () {
    // Create a new user who doesn't own the prompt run
    $otherUser = User::factory()->create();
    auth()->logout();
    $this->actingAs($otherUser);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'workflow_stage' => 'completed',
    ]);

    $response = $this->patch(route('prompt-builder.update-prompt', $promptRun), [
        'optimized_prompt' => 'New prompt',
    ]);

    $response->assertForbidden();
});
