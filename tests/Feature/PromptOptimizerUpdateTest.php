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
        'workflow_stage' => 'completed',
        'status' => 'completed',
        'optimized_prompt' => 'Original optimised prompt text',
    ]);

    $response = $this->patch(route('prompt-optimizer.update-prompt', $promptRun), [
        'optimized_prompt' => 'Updated and improved optimised prompt text',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Prompt updated successfully.');

    $promptRun->refresh();
    expect($promptRun->optimized_prompt)->toBe('Updated and improved optimised prompt text');
});

test('update optimised prompt validates required field', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'completed',
        'status' => 'completed',
    ]);

    $response = $this->patch(route('prompt-optimizer.update-prompt', $promptRun), []);

    $response->assertSessionHasErrors(['optimized_prompt']);
});

test('update optimised prompt validates string type', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'completed',
        'status' => 'completed',
    ]);

    $response = $this->patch(route('prompt-optimizer.update-prompt', $promptRun), [
        'optimized_prompt' => ['not', 'a', 'string'],
    ]);

    $response->assertSessionHasErrors(['optimized_prompt']);
});

test('update optimised prompt validates max length', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'completed',
        'status' => 'completed',
    ]);

    $response = $this->patch(route('prompt-optimizer.update-prompt', $promptRun), [
        'optimized_prompt' => str_repeat('a', 50001), // Over 50000 characters
    ]);

    $response->assertSessionHasErrors(['optimized_prompt']);
});

test('update optimised prompt only allows completed workflow stage', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'answering_questions', // Not completed
        'status' => 'processing',
    ]);

    $response = $this->patch(route('prompt-optimizer.update-prompt', $promptRun), [
        'optimized_prompt' => 'New prompt text',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Can only edit completed prompt runs.');

    // Verify prompt was not updated
    $promptRun->refresh();
    expect($promptRun->optimized_prompt)->not->toBe('New prompt text');
});

test('update optimised prompt rejects failed prompt runs', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'failed',
        'status' => 'failed',
    ]);

    $response = $this->patch(route('prompt-optimizer.update-prompt', $promptRun), [
        'optimized_prompt' => 'New prompt text',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Can only edit completed prompt runs.');
});

test('update optimised prompt rejects in progress prompt runs', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'generating_prompt',
        'status' => 'processing',
    ]);

    $response = $this->patch(route('prompt-optimizer.update-prompt', $promptRun), [
        'optimized_prompt' => 'New prompt text',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Can only edit completed prompt runs.');
});

test('user cannot update other users prompt runs', function () {
    $otherUser = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $otherUser->id,
        'workflow_stage' => 'completed',
        'status' => 'completed',
    ]);

    $response = $this->patch(route('prompt-optimizer.update-prompt', $promptRun), [
        'optimized_prompt' => 'Malicious update attempt',
    ]);

    $response->assertForbidden();
});

test('update optimised prompt allows very long prompts within limit', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'completed',
        'status' => 'completed',
    ]);

    $longPrompt = str_repeat('a', 49999); // Just under 50000 limit

    $response = $this->patch(route('prompt-optimizer.update-prompt', $promptRun), [
        'optimized_prompt' => $longPrompt,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $promptRun->refresh();
    expect($promptRun->optimized_prompt)->toBe($longPrompt);
});

test('update optimised prompt preserves other fields', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_description' => 'Original task',
        'selected_framework' => 'SMART Goals',
        'workflow_stage' => 'completed',
        'status' => 'completed',
        'optimized_prompt' => 'Original prompt',
    ]);

    $response = $this->patch(route('prompt-optimizer.update-prompt', $promptRun), [
        'optimized_prompt' => 'Updated prompt',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $promptRun->refresh();
    // Verify other fields are unchanged
    expect($promptRun->task_description)->toBe('Original task')
        ->and($promptRun->selected_framework)->toBe('SMART Goals')
        ->and($promptRun->workflow_stage)->toBe('completed')
        ->and($promptRun->optimized_prompt)->toBe('Updated prompt');
});

test('update optimised prompt supports unicode characters', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'completed',
        'status' => 'completed',
    ]);

    $unicodePrompt = 'Optimised prompt with emoji 🎯 and special chars: ñ, é, 中文, العربية';

    $response = $this->patch(route('prompt-optimizer.update-prompt', $promptRun), [
        'optimized_prompt' => $unicodePrompt,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $promptRun->refresh();
    expect($promptRun->optimized_prompt)->toBe($unicodePrompt);
});

test('update optimised prompt allows newlines and formatting', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'completed',
        'status' => 'completed',
    ]);

    $formattedPrompt = "Line 1\n\nLine 2\n\n- Bullet point 1\n- Bullet point 2\n\nEnd.";

    $response = $this->patch(route('prompt-optimizer.update-prompt', $promptRun), [
        'optimized_prompt' => $formattedPrompt,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $promptRun->refresh();
    expect($promptRun->optimized_prompt)->toBe($formattedPrompt);
});

test('update optimised prompt requires authentication', function () {
    auth()->logout();

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'completed',
        'status' => 'completed',
    ]);

    $response = $this->patch(route('prompt-optimizer.update-prompt', $promptRun), [
        'optimized_prompt' => 'New prompt',
    ]);

    // Guests get 403 Forbidden because they can't update prompt runs
    // (Authorization fails, not authentication)
    $response->assertForbidden();
});
