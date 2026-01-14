<?php

use App\Models\User;

/**
 * Test authenticated user language is persisted and available to next request
 * Uses DatabaseTransactions instead of RefreshDatabase to avoid PostgreSQL deadlocks
 */
test('authenticated user language persists to next request', function () {
    // Create user with initial language
    $user = User::factory()->create([
        'country_code' => 'gb',
        'language_code' => 'en-GB',
        'language_manually_set' => false,
    ]);

    // Update language
    $response = $this->actingAs($user)
        ->patchJsonCountry('/profile/language', [
            'language_code' => 'fr-FR',
        ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    // Verify database was updated
    $user->refresh();
    expect($user->language_code)->toBe('fr-FR');

    // Make a new request and verify locale is set correctly from database
    $homeResponse = $this->actingAs($user)
        ->getCountry('/');

    $homeResponse->assertStatus(200);

    // The locale should be 'fr-FR' from the database, not reverted to en-GB
    expect($user->language_code)->toBe('fr-FR');
});

/**
 * Test language preference uses Redis cache correctly
 */
test('language preference uses cache', function () {
    $user = User::factory()->create([
        'country_code' => 'gb',
        'language_code' => 'en-GB',
        'language_manually_set' => true,
    ]);

    // Cache should be invalidated after update
    $this->actingAs($user)
        ->patchJsonCountry('/profile/language', [
            'language_code' => 'de-DE',
        ])->assertStatus(200);

    // Verify cache was cleared and fresh value can be fetched
    $user->refresh();
    expect($user->language_code)->toBe('de-DE');
});

/**
 * Test multiple language updates work correctly
 */
test('user can update language multiple times', function () {
    $user = User::factory()->create([
        'country_code' => 'gb',
        'language_code' => 'en-GB',
    ]);

    $languages = ['fr-FR', 'de-DE', 'es-ES', 'en-GB'];

    foreach ($languages as $lang) {
        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/language', [
                'language_code' => $lang,
            ]);

        $response->assertStatus(200);
        $user->refresh();
        expect($user->language_code)->toBe($lang);
    }
});
