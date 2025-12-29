<?php

use App\Models\Feedback;
use App\Models\User;

/**
 * Unit tests for Feedback model
 */
describe('Feedback model', function () {
    test('findByUser returns feedback for user', function () {
        $user = User::factory()->create();
        $feedback = Feedback::factory()->create(['user_id' => $user->id]);

        $found = Feedback::findByUser($user->id);

        expect($found->id)->toBe($feedback->id);
    });

    test('findByUser returns null when user has no feedback', function () {
        $user = User::factory()->create();

        $found = Feedback::findByUser($user->id);

        expect($found)->toBeNull();
    });

    test('findByUser returns null for null user ID', function () {
        $found = Feedback::findByUser(null);

        expect($found)->toBeNull();
    });

    test('findByUser returns only the users feedback not others', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $feedback1 = Feedback::factory()->create(['user_id' => $user1->id]);
        $feedback2 = Feedback::factory()->create(['user_id' => $user2->id]);

        $found = Feedback::findByUser($user1->id);

        expect($found->id)->toBe($feedback1->id)
            ->and($found->id)->not->toBe($feedback2->id);
    });

    test('desired_features is automatically cast to array', function () {
        $feedback = Feedback::factory()->create([
            'desired_features' => ['templates', 'api-integration'],
        ]);

        $fresh = Feedback::find($feedback->id);

        expect($fresh->desired_features)
            ->toBeArray()
            ->toBe(['templates', 'api-integration']);
    });

    test('integer fields are cast correctly', function () {
        $feedback = Feedback::factory()->create([
            'experience_level' => 5,
            'usefulness' => 6,
            'usage_intent' => 7,
        ]);

        $fresh = Feedback::find($feedback->id);

        expect($fresh->experience_level)->toBeInt()->toBe(5)
            ->and($fresh->usefulness)->toBeInt()->toBe(6)
            ->and($fresh->usage_intent)->toBeInt()->toBe(7);
    });

    test('user relationship works', function () {
        $user = User::factory()->create(['name' => 'Test User']);
        $feedback = Feedback::factory()->create(['user_id' => $user->id]);

        expect($feedback->user->name)->toBe('Test User');
    });
});
