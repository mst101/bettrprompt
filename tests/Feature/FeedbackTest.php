<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('create feedback page is displayed for authenticated users', function () {
    $response = $this->get(route('feedback.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Feedback/Create')
    );
});

test('create feedback page redirects to show if feedback exists', function () {
    // Insert existing feedback
    DB::table('feedback')->insert([
        'user_id' => $this->user->id,
        'experience_level' => 4, // 1-7 scale
        'usefulness' => 4,
        'recommendation_likelihood' => 6, // 1-7 scale for NPS
        'suggestions' => null,
        'desired_features' => json_encode(['templates']),
        'desired_features_other' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->get(route('feedback.create'));

    $response->assertRedirect(route('feedback.show'));
});

test('show feedback page displays existing feedback', function () {
    // Insert feedback
    DB::table('feedback')->insert([
        'user_id' => $this->user->id,
        'experience_level' => 6, // 1-7 scale (advanced)
        'usefulness' => 5,
        'recommendation_likelihood' => 7, // 1-7 scale for NPS (very likely)
        'suggestions' => 'Great tool!',
        'desired_features' => json_encode(['templates', 'api-integration']),
        'desired_features_other' => 'Custom feature request',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->get(route('feedback.show'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Feedback/Show')
        ->where('feedback.experienceLevel', 6)
        ->where('feedback.usefulness', 5)
        ->where('feedback.recommendationLikelihood', 7)
        ->where('feedback.suggestions', 'Great tool!')
        ->where('feedback.desiredFeatures', ['templates', 'api-integration'])
        ->where('feedback.desiredFeaturesOther', 'Custom feature request')
    );
});

test('show feedback page redirects to create if no feedback exists', function () {
    $response = $this->get(route('feedback.show'));

    $response->assertRedirect(route('feedback.create'));
});

test('store feedback creates new feedback record', function () {
    $response = $this->post(route('feedback.store'), [
        'experience_level' => 2, // 1-7 scale (beginner)
        'usefulness' => 4,
        'recommendation_likelihood' => 6, // 1-7 scale
        'suggestions' => 'Could use more examples',
        'desired_features' => ['templates', 'compare'],
        'desired_features_other' => 'Integration with other tools',
    ]);

    $response->assertRedirect(route('prompt-optimizer.index'));
    $response->assertSessionHas('success', 'Thank you for your feedback!');

    $this->assertDatabaseHas('feedback', [
        'user_id' => $this->user->id,
        'experience_level' => 2,
        'usefulness' => 4,
        'recommendation_likelihood' => 6,
        'suggestions' => 'Could use more examples',
        'desired_features_other' => 'Integration with other tools',
    ]);
});

test('store feedback validates required fields', function () {
    $response = $this->post(route('feedback.store'), []);

    $response->assertSessionHasErrors(['experience_level', 'usefulness', 'recommendation_likelihood', 'desired_features']);
});

test('store feedback validates experience level range', function () {
    $response = $this->post(route('feedback.store'), [
        'experience_level' => 8, // Over max of 7
        'usefulness' => 4,
        'recommendation_likelihood' => 6,
        'desired_features' => ['templates'],
    ]);

    $response->assertSessionHasErrors(['experience_level']);
});

test('store feedback validates usefulness range', function () {
    $response = $this->post(route('feedback.store'), [
        'experience_level' => 2,
        'usefulness' => 8, // Max is 7
        'recommendation_likelihood' => 6,
        'desired_features' => ['templates'],
    ]);

    $response->assertSessionHasErrors(['usefulness']);
});

test('store feedback validates recommendation likelihood range', function () {
    $response = $this->post(route('feedback.store'), [
        'experience_level' => 2,
        'usefulness' => 4,
        'recommendation_likelihood' => 8, // Max is 7
        'desired_features' => ['templates'],
    ]);

    $response->assertSessionHasErrors(['recommendation_likelihood']);
});

test('store feedback allows null suggestions', function () {
    $response = $this->post(route('feedback.store'), [
        'experience_level' => 4,
        'usefulness' => 3,
        'recommendation_likelihood' => 5,
        'desired_features' => ['templates'],
    ]);

    $response->assertRedirect(route('prompt-optimizer.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('feedback', [
        'user_id' => $this->user->id,
        'suggestions' => null,
    ]);
});

test('store feedback allows null desired features other', function () {
    $response = $this->post(route('feedback.store'), [
        'experience_level' => 4,
        'usefulness' => 3,
        'recommendation_likelihood' => 5,
        'desired_features' => ['templates'],
    ]);

    $response->assertRedirect(route('prompt-optimizer.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('feedback', [
        'user_id' => $this->user->id,
        'desired_features_other' => null,
    ]);
});

test('update feedback updates existing feedback record', function () {
    // Insert existing feedback
    DB::table('feedback')->insert([
        'user_id' => $this->user->id,
        'experience_level' => 2, // beginner
        'usefulness' => 3,
        'recommendation_likelihood' => 4,
        'suggestions' => 'Original suggestion',
        'desired_features' => json_encode(['templates']),
        'desired_features_other' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->put(route('feedback.update'), [
        'experience_level' => 4, // intermediate
        'usefulness' => 5,
        'recommendation_likelihood' => 6,
        'suggestions' => 'Updated suggestion',
        'desired_features' => ['compare', 'api-integration'],
        'desired_features_other' => 'New feature idea',
    ]);

    $response->assertRedirect(route('prompt-optimizer.index'));
    $response->assertSessionHas('success', 'Thank you for updating your feedback!');

    $this->assertDatabaseHas('feedback', [
        'user_id' => $this->user->id,
        'experience_level' => 4,
        'usefulness' => 5,
        'recommendation_likelihood' => 6,
        'suggestions' => 'Updated suggestion',
        'desired_features_other' => 'New feature idea',
    ]);
});

test('update feedback validates required fields', function () {
    // Insert existing feedback
    DB::table('feedback')->insert([
        'user_id' => $this->user->id,
        'experience_level' => 2,
        'usefulness' => 3,
        'recommendation_likelihood' => 4,
        'suggestions' => null,
        'desired_features' => json_encode(['templates']),
        'desired_features_other' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->put(route('feedback.update'), []);

    $response->assertSessionHasErrors(['experience_level', 'usefulness', 'recommendation_likelihood', 'desired_features']);
});

test('feedback routes are accessible to guests', function () {
    auth()->logout();

    // Guests can access feedback create page
    $response = $this->get(route('feedback.create'));
    $response->assertOk();

    // Guests can access feedback show page (will redirect to create if no feedback)
    $response = $this->get(route('feedback.show'));
    $response->assertRedirect(route('feedback.create'));

    // Note: Actual storing of feedback is tested in other tests
    // This just verifies routes are accessible without auth
});

test('users can only see their own feedback', function () {
    // Create feedback for another user
    $otherUser = User::factory()->create();
    DB::table('feedback')->insert([
        'user_id' => $otherUser->id,
        'experience_level' => 6, // advanced
        'usefulness' => 5,
        'recommendation_likelihood' => 7,
        'suggestions' => null,
        'desired_features' => json_encode(['templates']),
        'desired_features_other' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Current user should see create page (no feedback exists for them)
    $response = $this->get(route('feedback.show'));
    $response->assertRedirect(route('feedback.create'));
});
