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
    $this->actingAs($this->user);
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

    $response = $this->post($this->localeRoute('prompt-builder.retry', [
        'promptRun' => $promptRun,
    ], absolute: false));

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

    $response = $this->post($this->localeRoute('prompt-builder.retry', [
        'promptRun' => $promptRun,
    ], absolute: false));

    $response->assertRedirect();

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe('0_processing');
});

test('user cannot retry other users prompt runs', function () {
    $otherUser = User::factory()->create();
    $otherRun = PromptRun::factory()->create([
        'user_id' => $otherUser->id,
        'workflow_stage' => '0_failed',
    ]);

    $response = $this->post($this->localeRoute('prompt-builder.retry', [
        'promptRun' => $otherRun,
    ], absolute: false));

    $response->assertForbidden();
});

test('cannot retry non-failed prompt runs', function () {
    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '2_completed',
    ]);

    $response = $this->post($this->localeRoute('prompt-builder.retry', [
        'promptRun' => $promptRun,
    ], absolute: false));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});
