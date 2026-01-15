<?php

use App\Models\Feedback;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('create feedback page is displayed for authenticated users', function () {
    $response = $this->getCountry(route('feedback.create', [], false));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Feedback/Create')
    );
});

test('create feedback page redirects to show if feedback exists', function () {
    // Create existing feedback using factory
    Feedback::factory()->create([
        'user_id' => $this->user->id,
        'experience_level' => 4,
        'usefulness' => 4,
        'usage_intent' => 6,
        'suggestions' => null,
        'desired_features' => ['templates'],
        'desired_features_other' => null,
    ]);

    $response = $this->getCountry(route('feedback.create', [], false));

    $response->assertRedirect($this->countryRoute('feedback.show'));
});

test('show feedback page displays existing feedback', function () {
    // Create feedback using factory
    Feedback::factory()->create([
        'user_id' => $this->user->id,
        'experience_level' => 6,
        'usefulness' => 5,
        'usage_intent' => 7,
        'suggestions' => 'Great tool!',
        'desired_features' => ['templates', 'api-integration'],
        'desired_features_other' => 'Custom feature request',
    ]);

    $response = $this->getCountry(route('feedback.show', [], false));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Feedback/Show')
        ->where('feedback.experienceLevel', 6)
        ->where('feedback.usefulness', 5)
        ->where('feedback.usageIntent', 7)
        ->where('feedback.suggestions', 'Great tool!')
        ->where('feedback.desiredFeatures', ['templates', 'api-integration'])
        ->where('feedback.desiredFeaturesOther', 'Custom feature request')
    );
});

test('show feedback page redirects to create if no feedback exists', function () {
    $response = $this->getCountry(route('feedback.show', [], false));

    $response->assertRedirect($this->countryRoute('feedback.create'));
});

test('store feedback creates new feedback record', function () {
    $response = $this->postCountry(route('feedback.store', [], false), [
        'experience_level' => 2, // 1-7 scale (beginner)
        'usefulness' => 4,
        'usage_intent' => 6, // 1-7 scale
        'suggestions' => 'Could use more debug',
        'desired_features' => ['templates', 'compare'],
        'desired_features_other' => 'Integration with other tools',
    ]);

    $response->assertRedirect($this->countryRoute('feedback.thank-you'));
    $response->assertSessionHas('success', 'Thank you for your feedback!');

    $this->assertDatabaseHas('feedback', [
        'user_id' => $this->user->id,
        'experience_level' => 2,
        'usefulness' => 4,
        'usage_intent' => 6,
        'suggestions' => 'Could use more debug',
        'desired_features_other' => 'Integration with other tools',
    ]);
});

test('store feedback validates required fields', function () {
    $response = $this->postCountry(route('feedback.store', [], false), []);

    $response->assertSessionHasErrors(['experience_level', 'usefulness', 'usage_intent', 'desired_features']);
    $this->assertDatabaseEmpty('feedback');
});

test('store feedback validates experience level range', function () {
    $response = $this->postCountry(route('feedback.store', [], false), [
        'experience_level' => 8, // Over max of 7
        'usefulness' => 4,
        'usage_intent' => 6,
        'desired_features' => ['templates'],
    ]);

    $response->assertSessionHasErrors(['experience_level']);
});

test('store feedback validates usefulness range', function () {
    $response = $this->postCountry(route('feedback.store', [], false), [
        'experience_level' => 2,
        'usefulness' => 8, // Max is 7
        'usage_intent' => 6,
        'desired_features' => ['templates'],
    ]);

    $response->assertSessionHasErrors(['usefulness']);
});

test('store feedback validates recommendation likelihood range', function () {
    $response = $this->postCountry(route('feedback.store', [], false), [
        'experience_level' => 2,
        'usefulness' => 4,
        'usage_intent' => 8, // Max is 7
        'desired_features' => ['templates'],
    ]);

    $response->assertSessionHasErrors(['usage_intent']);
});

test('store feedback allows optional fields to be null', function () {
    $response = $this->postCountry(route('feedback.store', [], false), [
        'experience_level' => 4,
        'usefulness' => 3,
        'usage_intent' => 5,
        'desired_features' => ['templates'],
    ]);

    $response->assertRedirect($this->countryRoute('feedback.thank-you'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('feedback', [
        'user_id' => $this->user->id,
        'suggestions' => null,
        'desired_features_other' => null,
    ]);
});

test('update feedback updates existing feedback record', function () {
    // Create existing feedback using factory
    Feedback::factory()->create([
        'user_id' => $this->user->id,
        'experience_level' => 2,
        'usefulness' => 3,
        'usage_intent' => 4,
        'suggestions' => 'Original suggestion',
        'desired_features' => ['templates'],
        'desired_features_other' => null,
    ]);

    $response = $this->putCountry(route('feedback.update', [], false), [
        'experience_level' => 4,
        'usefulness' => 5,
        'usage_intent' => 6,
        'suggestions' => 'Updated suggestion',
        'desired_features' => ['compare', 'api-integration'],
        'desired_features_other' => 'New feature idea',
    ]);

    $response->assertRedirect($this->countryRoute('feedback.show'));
    $response->assertSessionHas('success', 'Thank you for updating your feedback!');

    $this->assertDatabaseHas('feedback', [
        'user_id' => $this->user->id,
        'experience_level' => 4,
        'usefulness' => 5,
        'usage_intent' => 6,
        'suggestions' => 'Updated suggestion',
        'desired_features_other' => 'New feature idea',
    ]);
});

test('update feedback validates required fields', function () {
    // Create existing feedback using factory
    Feedback::factory()->create([
        'user_id' => $this->user->id,
        'experience_level' => 2,
        'usefulness' => 3,
        'usage_intent' => 4,
        'suggestions' => null,
        'desired_features' => ['templates'],
        'desired_features_other' => null,
    ]);

    $response = $this->putCountry(route('feedback.update', [], false), []);

    $response->assertSessionHasErrors(['experience_level', 'usefulness', 'usage_intent', 'desired_features']);
});

test('feedback routes are accessible to guests', function () {
    auth()->logout();

    // Guests can access feedback create page
    $response = $this->getCountry(route('feedback.create', [], false));
    $response->assertOk();

    // Guests can access feedback show page (will redirect to create if no feedback)
    $response = $this->getCountry(route('feedback.show', [], false));
    $response->assertRedirect($this->countryRoute('feedback.create'));
});

test('guests can store feedback', function () {
    auth()->logout();

    $response = $this->postCountry(route('feedback.store', [], false), [
        'experience_level' => 3,
        'usefulness' => 4,
        'usage_intent' => 5,
        'suggestions' => 'Great application!',
        'desired_features' => ['templates', 'api-integration'],
        'desired_features_other' => null,
    ]);

    $response->assertRedirect($this->countryRoute('feedback.thank-you'));
    $response->assertSessionHas('success', 'Thank you for your feedback!');

    $this->assertDatabaseHas('feedback', [
        'user_id' => null,
        'experience_level' => 3,
        'usefulness' => 4,
        'usage_intent' => 5,
        'suggestions' => 'Great application!',
    ]);
});

test('users can only see their own feedback', function () {
    // Create feedback for another user
    $otherUser = User::factory()->create();
    Feedback::factory()->create([
        'user_id' => $otherUser->id,
        'experience_level' => 6,
        'usefulness' => 5,
        'usage_intent' => 7,
        'suggestions' => null,
        'desired_features' => ['templates'],
        'desired_features_other' => null,
    ]);

    // Current user should see create page (no feedback exists for them)
    $response = $this->getCountry(route('feedback.show', [], false));
    $response->assertRedirect($this->countryRoute('feedback.create'));

    // Verify other user's feedback is not accessible
    $this->actingAs($otherUser);
    $response = $this->getCountry(route('feedback.show', [], false));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->where('feedback.experienceLevel', 6));
});
