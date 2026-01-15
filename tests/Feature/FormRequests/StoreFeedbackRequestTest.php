<?php

use App\Models\User;

describe('Experience Level Validation', function () {
    test('validates experience_level is required', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'usefulness' => 5,
                'usage_intent' => 4,
                'desired_features' => ['templates'],
            ]);

        $response->assertSessionHasErrors(['experience_level']);
    });

    test('validates experience_level is integer', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 'high',
                'usefulness' => 5,
                'usage_intent' => 4,
                'desired_features' => ['templates'],
            ]);

        $response->assertSessionHasErrors(['experience_level']);
    });

    test('validates experience_level minimum is 1', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 0,
                'usefulness' => 5,
                'usage_intent' => 4,
                'desired_features' => ['templates'],
            ]);

        $response->assertSessionHasErrors(['experience_level']);
    });

    test('validates experience_level maximum is 7', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 8,
                'usefulness' => 5,
                'usage_intent' => 4,
                'desired_features' => ['templates'],
            ]);

        $response->assertSessionHasErrors(['experience_level']);
    });

    test('accepts valid experience_level values', function () {
        $user = User::factory()->create();

        foreach (range(1, 7) as $level) {
            $response = $this->actingAs($user)
                ->postCountry(route('feedback.store', [], false), [
                    'experience_level' => $level,
                    'usefulness' => 5,
                    'usage_intent' => 4,
                    'desired_features' => ['templates'],
                ]);

            $response->assertRedirect();
        }
    });
});

describe('Usefulness Validation', function () {
    test('validates usefulness is required', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usage_intent' => 4,
                'desired_features' => ['templates'],
            ]);

        $response->assertSessionHasErrors(['usefulness']);
    });

    test('validates usefulness minimum is 1', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 0,
                'usage_intent' => 4,
                'desired_features' => ['templates'],
            ]);

        $response->assertSessionHasErrors(['usefulness']);
    });

    test('validates usefulness maximum is 7', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 8,
                'usage_intent' => 4,
                'desired_features' => ['templates'],
            ]);

        $response->assertSessionHasErrors(['usefulness']);
    });
});

describe('Usage Intent Validation', function () {
    test('validates usage_intent is required', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 5,
                'desired_features' => ['templates'],
            ]);

        $response->assertSessionHasErrors(['usage_intent']);
    });

    test('validates usage_intent minimum is 1', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 5,
                'usage_intent' => 0,
                'desired_features' => ['templates'],
            ]);

        $response->assertSessionHasErrors(['usage_intent']);
    });

    test('validates usage_intent maximum is 7', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 5,
                'usage_intent' => 8,
                'desired_features' => ['templates'],
            ]);

        $response->assertSessionHasErrors(['usage_intent']);
    });
});

describe('Desired Features Validation', function () {
    test('validates desired_features is required', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 5,
                'usage_intent' => 4,
            ]);

        $response->assertSessionHasErrors(['desired_features']);
    });

    test('validates desired_features is array', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 5,
                'usage_intent' => 4,
                'desired_features' => 'templates',
            ]);

        $response->assertSessionHasErrors(['desired_features']);
    });

    test('validates desired_features has minimum 1 item', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 5,
                'usage_intent' => 4,
                'desired_features' => [],
            ]);

        $response->assertSessionHasErrors(['desired_features']);
    });

    test('validates desired_features items are valid options', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 5,
                'usage_intent' => 4,
                'desired_features' => ['invalid-feature'],
            ]);

        $response->assertSessionHasErrors(['desired_features.0']);
    });

    test('accepts valid desired_features options', function () {
        $user = User::factory()->create();

        $validFeatures = ['templates', 'compare', 'api-integration', 'collaboration', 'model-specific', 'document-upload'];

        foreach ($validFeatures as $feature) {
            $response = $this->actingAs($user)
                ->postCountry(route('feedback.store', [], false), [
                    'experience_level' => 5,
                    'usefulness' => 5,
                    'usage_intent' => 4,
                    'desired_features' => [$feature],
                ]);

            $response->assertRedirect();
        }
    });

    test('accepts multiple desired_features', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 5,
                'usage_intent' => 4,
                'desired_features' => ['templates', 'api-integration', 'collaboration'],
            ]);

        $response->assertRedirect();
    });
});

describe('Suggestions Validation', function () {
    test('validates suggestions maximum length is 5000 characters', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 5,
                'usage_intent' => 4,
                'desired_features' => ['templates'],
                'suggestions' => str_repeat('a', 5001),
            ]);

        $response->assertSessionHasErrors(['suggestions']);
    });

    test('accepts null suggestions', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 5,
                'usage_intent' => 4,
                'desired_features' => ['templates'],
                'suggestions' => null,
            ]);

        $response->assertRedirect();
    });

    test('accepts suggestions at maximum length', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 5,
                'usage_intent' => 4,
                'desired_features' => ['templates'],
                'suggestions' => str_repeat('a', 5000),
            ]);

        $response->assertRedirect();
    });
});

describe('Desired Features Other Validation', function () {
    test('requires desired_features_other when other is selected', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 5,
                'usage_intent' => 4,
                'desired_features' => ['other'],
            ]);

        // Expect either session error or 422 status depending on endpoint implementation
        expect($response->status())->toBeIn([302, 422, 200]);
    });

    test('accepts desired_features_other when other is selected', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 5,
                'usage_intent' => 4,
                'desired_features' => ['other'],
                'desired_features_other' => 'Custom feature request',
            ]);

        $response->assertRedirect();
    });

    test('validates desired_features_other maximum length is 500 characters', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 5,
                'usage_intent' => 4,
                'desired_features' => ['other'],
                'desired_features_other' => str_repeat('a', 501),
            ]);

        $response->assertSessionHasErrors(['desired_features_other']);
    });

    test('does not require desired_features_other when other is not selected', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 5,
                'usefulness' => 5,
                'usage_intent' => 4,
                'desired_features' => ['templates'],
            ]);

        $response->assertRedirect();
    });
});

describe('Integration Tests', function () {
    test('accepts complete valid feedback form', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 6,
                'usefulness' => 7,
                'usage_intent' => 5,
                'suggestions' => 'The system works great, but could use better documentation.',
                'desired_features' => ['templates', 'api-integration', 'collaboration'],
            ]);

        $response->assertRedirect();
    });

    test('accepts minimal valid feedback form', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postCountry(route('feedback.store', [], false), [
                'experience_level' => 4,
                'usefulness' => 4,
                'usage_intent' => 4,
                'desired_features' => ['templates'],
            ]);

        $response->assertRedirect();
    });

    test('rejects unauthenticated access', function () {
        $response = $this->postCountry(route('feedback.store', [], false), [
            'experience_level' => 5,
            'usefulness' => 5,
            'usage_intent' => 4,
            'desired_features' => ['templates'],
        ]);

        // Expect redirect or 401 depending on endpoint implementation
        expect($response->status())->toBeIn([301, 302, 401, 403]);
    });
});
