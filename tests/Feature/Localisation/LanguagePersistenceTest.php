<?php

use App\Models\User;
use App\Models\Visitor;

/**
 * Test authenticated user can update language preference
 */
test('authenticated user can update language', function () {
    $user = User::factory()->create([
        'language_code' => 'en-GB',
        'language_manually_set' => false,
    ]);

    $response = $this->actingAs($user)
        ->patchJsonCountry('/profile/language', [
            'language_code' => 'fr-FR',
        ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $user->refresh();
    expect($user->language_code)->toBe('fr-FR')
        ->and($user->language_manually_set)->toBeTrue();
});

/**
 * Test visitor can update language preference
 */
test('visitor can update language', function () {
    $visitor = Visitor::factory()->create([
        'language_code' => 'en-GB',
    ]);

    // Direct controller call avoids auth helpers that assume an authenticated user.
    $request = \Illuminate\Http\Request::create('/gb/visitor/language', 'PATCH', [
        'language_code' => 'de-DE',
    ]);

    // Manually set the cookie on the request
    $request->cookies->set('visitor_id', (string) $visitor->id);

    $controller = new \App\Http\Controllers\VisitorController;
    $response = $controller->updateLanguage($request);

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true))->toBe(['success' => true]);

    $visitor->refresh();
    expect($visitor->language_code)->toBe('de-DE');
});

/**
 * Test language code validation - must be supported
 */
test('language code must be supported', function () {
    $user = User::factory()->create();
    $originalLanguage = $user->language_code;

    $response = $this->actingAs($user)
        ->patchJsonCountry('/profile/language', [
            'language_code' => 'xx-XX', // Unsupported language
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('language_code');

    $user->refresh();
    // Language should not change
    expect($user->language_code)->toBe($originalLanguage);
});

/**
 * Test language code is required
 */
test('language code is required', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patchJsonCountry('/profile/language', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('language_code');
});

/**
 * Test unauthenticated user cannot update profile language
 */
test('unauthenticated user cannot update profile language', function () {
    $response = $this->patchJsonCountry('/profile/language', [
        'language_code' => 'fr-FR',
    ]);

    // JSON requests return 401 Unauthorized for unauthenticated users
    $response->assertStatus(401);
});

/**
 * Test visitor language update without visitor_id cookie succeeds silently
 */
test('visitor language update without cookie succeeds silently', function () {
    $response = $this->patchJsonCountry('/visitor/language', [
        'language_code' => 'es-ES',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    // No record should be created/updated since there's no visitor_id
});

/**
 * Test updating language multiple times
 */
test('user can update language multiple times', function () {
    $user = User::factory()->create(['language_code' => 'en-GB']);

    // First update
    $this->actingAs($user)
        ->patchJsonCountry('/profile/language', ['language_code' => 'fr-FR']);
    $user->refresh();
    expect($user->language_code)->toBe('fr-FR');

    // Second update
    $this->actingAs($user)
        ->patchJsonCountry('/profile/language', ['language_code' => 'de-DE']);
    $user->refresh();
    expect($user->language_code)->toBe('de-DE');

    // Third update
    $this->actingAs($user)
        ->patchJsonCountry('/profile/language', ['language_code' => 'es-ES']);
    $user->refresh();
    expect($user->language_code)->toBe('es-ES');
});

/**
 * Test supported languages can be set
 */
test('supported languages can be set', function () {
    $user = User::factory()->create();
    $supportedLanguages = ['en-GB', 'en-US', 'de-DE', 'fr-FR', 'es-ES'];

    foreach ($supportedLanguages as $language) {
        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/language', [
                'language_code' => $language,
            ]);

        $response->assertStatus(200);
        $user->refresh();
        expect($user->language_code)->toBe($language);
    }
});

/**
 * Test language_manually_set flag is set when user updates language
 */
test('language_manually_set flag is updated', function () {
    $user = User::factory()->create([
        'language_code' => 'en-US',
        'language_manually_set' => false,
    ]);

    $this->actingAs($user)
        ->patchJsonCountry('/profile/language', [
            'language_code' => 'fr-FR',
        ]);

    $user->refresh();
    expect($user->language_manually_set)->toBeTrue();
});

/**
 * Test language persists in database for subsequent requests
 */
test('language persists in database', function () {
    $user = User::factory()->create(['language_code' => 'en-GB']);

    // Update language
    $this->actingAs($user)
        ->patchJsonCountry('/profile/language', [
            'language_code' => 'fr-FR',
        ]);

    // Fetch fresh instance from database
    $freshUser = User::find($user->id);
    expect($freshUser->language_code)->toBe('fr-FR');
});

/**
 * Test language code is case-sensitive in validation
 */
test('language code validation is case sensitive', function () {
    $user = User::factory()->create();

    // en-gb (lowercase) should not match en-GB (configured case)
    $response = $this->actingAs($user)
        ->patchJsonCountry('/profile/language', [
            'language_code' => 'en-gb',
        ]);

    // Should fail due to case sensitivity
    $response->assertStatus(422);
});

/**
 * Test language code with maximum length
 */
test('language code max length validation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patchJsonCountry('/profile/language', [
            'language_code' => 'this-is-way-too-long-for-a-locale-code',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('language_code');
});
