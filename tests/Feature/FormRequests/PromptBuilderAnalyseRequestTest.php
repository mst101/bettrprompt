<?php

use App\Models\User;

describe('Task Description Validation', function () {
    test('validates task_description is required', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), []);

        $response->assertSessionHasErrors(['task_description']);
    });

    test('validates task_description minimum length is 10 characters', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'short',
            ]);

        $response->assertSessionHasErrors(['task_description']);
    });

    test('validates task_description maximum length is 5000 characters', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => str_repeat('a', 5001),
            ]);

        $response->assertSessionHasErrors(['task_description']);
    });

    test('accepts valid task_description at minimum length', function () {
        Queue::fake();
        $user = User::factory()->create();

        // Exactly 10 characters
        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'tencharac',
            ]);

        $response->assertRedirect();
    });

    test('accepts valid task_description at maximum length', function () {
        Queue::fake();
        $user = User::factory()->create();

        // Exactly 5000 characters
        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => str_repeat('a', 5000),
            ]);

        $response->assertRedirect();
    });

    test('rejects non-string task_description', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 123456,
            ]);

        $response->assertSessionHasErrors(['task_description']);
    });
});

describe('Personality Type Validation', function () {
    test('accepts valid personality types', function () {
        Queue::fake();
        $user = User::factory()->create();

        $validTypes = ['INTJ-A', 'INFP-T', 'ENFP-A', 'ISTJ-T', 'ESFJ-A'];

        foreach ($validTypes as $type) {
            $response = $this->actingAs($user)
                ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                    'task_description' => 'Valid task description',
                    'personality_type' => $type,
                ]);

            $response->assertRedirect();
        }
    });

    test('rejects invalid personality type format', function () {
        $user = User::factory()->create();

        $invalidTypes = [
            'INTJ', // Missing tier
            'INTJ-B', // Invalid tier
            'INT-A', // Too short
            'INTJPA', // Missing hyphen
            'intj-a', // Lowercase
            '1234-A', // Numbers
        ];

        foreach ($invalidTypes as $type) {
            $response = $this->actingAs($user)
                ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                    'task_description' => 'Valid task description',
                    'personality_type' => $type,
                ]);

            $response->assertSessionHasErrors(['personality_type']);
        }
    });

    test('accepts null personality_type', function () {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'Valid task description',
                'personality_type' => null,
            ]);

        $response->assertRedirect();
    });
});

describe('Trait Percentages Validation', function () {
    test('accepts valid trait percentages', function () {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'Valid task description',
                'trait_percentages' => [
                    'mind' => 75,
                    'energy' => 55,
                    'nature' => 80,
                    'tactics' => 70,
                    'identity' => 65,
                ],
            ]);

        $response->assertRedirect();
    });

    test('validates trait percentage minimum is 50', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'Valid task description',
                'trait_percentages' => [
                    'mind' => 49, // Below minimum
                ],
            ]);

        $response->assertSessionHasErrors(['trait_percentages.mind']);
    });

    test('validates trait percentage maximum is 100', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'Valid task description',
                'trait_percentages' => [
                    'energy' => 101, // Above maximum
                ],
            ]);

        $response->assertSessionHasErrors(['trait_percentages.energy']);
    });

    test('accepts boundary values for trait percentages', function () {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'Valid task description',
                'trait_percentages' => [
                    'mind' => 50,
                    'energy' => 100,
                    'nature' => 75,
                    'tactics' => 50,
                    'identity' => 100,
                ],
            ]);

        $response->assertRedirect();
    });

    test('accepts null trait_percentages', function () {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'Valid task description',
                'trait_percentages' => null,
            ]);

        $response->assertRedirect();
    });

    test('accepts array with null individual percentages', function () {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'Valid task description',
                'trait_percentages' => [
                    'mind' => 75,
                    'energy' => null,
                    'nature' => 80,
                ],
            ]);

        $response->assertRedirect();
    });

    test('rejects non-integer trait percentages', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'Valid task description',
                'trait_percentages' => [
                    'mind' => '75.5',
                ],
            ]);

        $response->assertSessionHasErrors(['trait_percentages.mind']);
    });
});

describe('Integration Tests', function () {
    test('accepts complete valid form', function () {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'Create a comprehensive strategic plan for product launch',
                'personality_type' => 'INTJ-A',
                'trait_percentages' => [
                    'mind' => 80,
                    'energy' => 60,
                    'nature' => 75,
                    'tactics' => 70,
                    'identity' => 65,
                ],
            ]);

        $response->assertRedirect();
    });

    test('accepts minimal valid form', function () {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'Minimum length task description here',
            ]);

        $response->assertRedirect();
    });
});
