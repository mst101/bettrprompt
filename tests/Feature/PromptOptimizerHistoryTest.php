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
    // Create multiple prompt runs
    PromptRun::factory()->count(3)->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->get(route('prompt-optimizer.history'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('PromptOptimizer/History')
        ->has('promptRuns.data', 3)
        ->where('filters.sort_by', 'created_at')
        ->where('filters.sort_direction', 'desc')
    );
});

test('history page sorts by created at ascending', function () {
    $oldRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'created_at' => now()->subDays(3),
        'task_description' => 'Old task',
    ]);

    $newRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'created_at' => now()->subDay(),
        'task_description' => 'New task',
    ]);

    $response = $this->get(route('prompt-optimizer.history', [
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
    // Update user to have personality type
    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'personality_type' => 'ENFP',
        'task_description' => 'Task A',
    ]);

    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'personality_type' => 'INTJ',
        'task_description' => 'Task B',
    ]);

    $response = $this->get(route('prompt-optimizer.history', [
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
        'status' => 'completed',
    ]);

    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'failed',
    ]);

    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'processing',
    ]);

    $response = $this->get(route('prompt-optimizer.history', [
        'sort_by' => 'status',
        'sort_direction' => 'asc',
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.sort_by', 'status')
    );
});

test('history page sorts by selected framework', function () {
    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'selected_framework' => 'SMART Goals',
    ]);

    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'selected_framework' => 'Brainstorming',
    ]);

    $response = $this->get(route('prompt-optimizer.history', [
        'sort_by' => 'selected_framework',
        'sort_direction' => 'asc',
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.sort_by', 'selected_framework')
    );
});

test('history page rejects invalid sort column', function () {
    PromptRun::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->get(route('prompt-optimizer.history', [
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
    ]);

    $response = $this->get(route('prompt-optimizer.history', [
        'sort_direction' => 'invalid',
    ]));

    $response->assertOk();
    // Should default to desc
    $response->assertInertia(fn ($page) => $page
        ->where('filters.sort_direction', 'desc')
    );
});

test('history page pagination works correctly', function () {
    // Create 15 prompt runs
    PromptRun::factory()->count(15)->create([
        'user_id' => $this->user->id,
    ]);

    // Default per_page is 6
    $response = $this->get(route('prompt-optimizer.history'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('promptRuns.data', 6) // Should show 6 per page
        ->where('promptRuns.meta.total', 15)
    );
});

test('history page respects custom per page parameter', function () {
    // Create 15 prompt runs
    PromptRun::factory()->count(15)->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->get(route('prompt-optimizer.history', [
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
    ]);

    $response = $this->get(route('prompt-optimizer.history', [
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
    ]);

    $response = $this->get(route('prompt-optimizer.history', [
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
    ]);

    $response = $this->get(route('prompt-optimizer.history', [
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
    ]);

    $response = $this->get(route('prompt-optimizer.history', [
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
    // Create prompt runs for this user
    PromptRun::factory()->count(3)->create([
        'user_id' => $this->user->id,
    ]);

    // Create prompt runs for another user
    $otherUser = User::factory()->create();
    PromptRun::factory()->count(5)->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->get(route('prompt-optimizer.history'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('promptRuns.data', 3) // Should only see own runs
        ->where('promptRuns.meta.total', 3)
    );
});

test('history page shows empty result for user with no prompt runs', function () {
    $response = $this->get(route('prompt-optimizer.history'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('promptRuns.data', 0)
        ->where('promptRuns.meta.total', 0)
    );
});

test('history page requires authentication', function () {
    auth()->logout();

    $response = $this->get(route('prompt-optimizer.history'));

    $response->assertRedirect(route('login'));
});

test('history page sorts by task description', function () {
    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_description' => 'Zebra task',
    ]);

    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_description' => 'Alpha task',
    ]);

    $response = $this->get(route('prompt-optimizer.history', [
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
        'personality_type' => 'INTJ',
    ]);

    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'personality_type' => null, // No personality type
    ]);

    $response = $this->get(route('prompt-optimizer.history', [
        'sort_by' => 'personality_type',
        'sort_direction' => 'asc',
    ]));

    $response->assertOk();
    // Should handle null values without error
    $response->assertInertia(fn ($page) => $page
        ->has('promptRuns.data', 2)
    );
});

test('history page handles sorting nulls in selected framework', function () {
    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'selected_framework' => 'SMART Goals',
    ]);

    PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'selected_framework' => null, // No framework yet
    ]);

    $response = $this->get(route('prompt-optimizer.history', [
        'sort_by' => 'selected_framework',
        'sort_direction' => 'asc',
    ]));

    $response->assertOk();
    // Should handle null values without error
    $response->assertInertia(fn ($page) => $page
        ->has('promptRuns.data', 2)
    );
});
