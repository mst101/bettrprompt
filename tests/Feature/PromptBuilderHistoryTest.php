<?php

use App\Models\PromptRun;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'personality_type' => 'INTJ',
    ]);

    $this->actingAs($this->user);
});

test('history page displays with default sorting', function () {
    // Create multiple PromptBuilder runs (with task_classification)
    PromptRun::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
    ]);

    $response = $this->get(route('prompt-builder.history'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('PromptBuilder/History')
        ->has('promptRuns.data', 3)
        ->where('filters.sort_by', 'created_at')
        ->where('filters.sort_direction', 'desc')
    );
});

test('history page sorts by created at ascending', function () {
    $oldRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'created_at' => now()->subDays(3),
        'task_description' => 'Old task',
    ]);

    $newRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'created_at' => now()->subDay(),
        'task_description' => 'New task',
    ]);

    $response = $this->get(route('prompt-builder.history', [
        'sort_by' => 'created_at',
        'sort_direction' => 'asc',
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('promptRuns.data.0.id', $oldRun->id)
        ->where('promptRuns.data.1.id', $newRun->id)
        ->where('filters.sort_direction', 'asc')
    );
});

test('history page sorts by personality type', function () {
    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'personality_type' => 'ENFP',
        'task_description' => 'Task A',
    ]);

    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'personality_type' => 'INTJ',
        'task_description' => 'Task B',
    ]);

    $response = $this->get(route('prompt-builder.history', [
        'sort_by' => 'personality_type',
        'sort_direction' => 'asc',
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.sort_by', 'personality_type')
        ->where('filters.sort_direction', 'asc')
    );
});

test('history page sorts by status', function () {
    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'status' => 'completed',
    ]);

    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'status' => 'failed',
    ]);

    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'status' => 'processing',
    ]);

    $response = $this->get(route('prompt-builder.history', [
        'sort_by' => 'status',
        'sort_direction' => 'asc',
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.sort_by', 'status')
    );
});

test('history page rejects invalid sort column', function () {
    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
    ]);

    $response = $this->get(route('prompt-builder.history', [
        'sort_by' => 'invalid_column',
    ]));

    $response->assertOk();
    // Should default to created_at
    $response->assertInertia(fn ($page) => $page
        ->where('filters.sort_by', 'created_at')
    );
});

test('history page rejects invalid sort direction', function () {
    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
    ]);

    $response = $this->get(route('prompt-builder.history', [
        'sort_direction' => 'invalid',
    ]));

    $response->assertOk();
    // Should default to desc
    $response->assertInertia(fn ($page) => $page
        ->where('filters.sort_direction', 'desc')
    );
});

test('history page pagination works correctly', function () {
    // Create 15 PromptBuilder runs
    PromptRun::factory()->count(15)->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
    ]);

    // Default per_page is 6
    $response = $this->get(route('prompt-builder.history'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('promptRuns.data', 6) // Should show 6 per page
        ->where('promptRuns.meta.total', 15)
    );
});

test('history page respects custom per page parameter', function () {
    // Create 15 PromptBuilder runs
    PromptRun::factory()->count(15)->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
    ]);

    $response = $this->get(route('prompt-builder.history', [
        'per_page' => 10,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('promptRuns.data', 10)
        ->where('filters.per_page', 10)
    );
});

test('history page clamps per page to maximum of 100', function () {
    PromptRun::factory()->count(5)->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
    ]);

    $response = $this->get(route('prompt-builder.history', [
        'per_page' => 500, // Exceeds max
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.per_page', 100) // Should be clamped to 100
    );
});

test('history page clamps per page to minimum of 1', function () {
    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
    ]);

    $response = $this->get(route('prompt-builder.history', [
        'per_page' => -5, // Negative value
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.per_page', 1) // Should be clamped to 1
    );
});

test('history page handles non numeric per page gracefully', function () {
    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
    ]);

    $response = $this->get(route('prompt-builder.history', [
        'per_page' => 'invalid',
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.per_page', 6) // Should default to 6
    );
});

test('history page pagination maintains query parameters', function () {
    PromptRun::factory()->count(15)->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
    ]);

    $response = $this->get(route('prompt-builder.history', [
        'sort_by' => 'status',
        'sort_direction' => 'asc',
        'per_page' => 5,
        'page' => 2,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.sort_by', 'status')
        ->where('filters.sort_direction', 'asc')
        ->where('filters.per_page', 5)
    );
});

test('history page does not show other users prompt runs', function () {
    // Create PromptBuilder runs for this user
    PromptRun::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
    ]);

    // Create PromptBuilder runs for another user
    $otherUser = User::factory()->create();
    PromptRun::factory()->count(5)->create([
        'user_id' => $otherUser->id,
        'task_classification' => ['category' => 'planning'],
    ]);

    $response = $this->get(route('prompt-builder.history'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('promptRuns.data', 3) // Should only see own runs
        ->where('promptRuns.meta.total', 3)
    );
});

test('history page shows empty result for user with no prompt runs', function () {
    $response = $this->get(route('prompt-builder.history'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('promptRuns.data', 0)
        ->where('promptRuns.meta.total', 0)
    );
});

test('history page requires authentication', function () {
    auth()->logout();

    $response = $this->get(route('prompt-builder.history'));

    $response->assertRedirect(route('login'));
});

test('history page sorts by task description', function () {
    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'task_description' => 'Zebra task',
    ]);

    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'task_description' => 'Alpha task',
    ]);

    $response = $this->get(route('prompt-builder.history', [
        'sort_by' => 'task_description',
        'sort_direction' => 'asc',
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.sort_by', 'task_description')
        ->where('promptRuns.data.0.taskDescription', 'Alpha task')
        ->where('promptRuns.data.1.taskDescription', 'Zebra task')
    );
});

test('history page handles sorting nulls in personality type', function () {
    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'personality_type' => 'INTJ',
    ]);

    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'personality_type' => null, // No personality type
    ]);

    $response = $this->get(route('prompt-builder.history', [
        'sort_by' => 'personality_type',
        'sort_direction' => 'asc',
    ]));

    $response->assertOk();
    // Should handle null values without error
    $response->assertInertia(fn ($page) => $page
        ->has('promptRuns.data', 2)
    );
});

test('history page only shows PromptBuilder runs with task classification', function () {
    // Create PromptBuilder runs (with task_classification)
    PromptRun::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
    ]);

    $response = $this->get(route('prompt-builder.history'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('promptRuns.data', 3) // Should only see PromptBuilder runs
        ->where('promptRuns.meta.total', 3)
    );
});
