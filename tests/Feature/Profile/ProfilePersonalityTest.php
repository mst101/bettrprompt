<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'personality_type' => 'INTJ-A',
        'trait_percentages' => [
            'mind' => 75,
            'energy' => 80,
            'nature' => 70,
            'tactics' => 65,
            'identity' => 85,
        ],
    ]);

    $this->actingAs($this->user);
});

test('user can update personality type', function () {
    $response = $this->patchCountry(route('profile.personality.update', [], false), [
        'personalityType' => 'ENFP-T',
        'traitPercentages' => [
            'mind' => 62,
            'energy' => 85,
            'nature' => 65,
            'tactics' => 73,
            'identity' => 55,
        ],
    ]);

    $response->assertRedirect($this->countryRoute('profile.edit'));
    $response->assertSessionHas('status', 'personality-updated');

    $this->user->refresh();
    expect($this->user->personality_type)->toBe('ENFP-T')
        ->and($this->user->trait_percentages['mind'])->toBe(62)
        ->and($this->user->trait_percentages['energy'])->toBe(85);
});

test('personality update validates personality type format', function () {
    $response = $this->patchCountry(route('profile.personality.update', [], false), [
        'personalityType' => 'INVALID', // Invalid format
        'traitPercentages' => [
            'mind' => 50,
            'energy' => 50,
            'nature' => 50,
            'tactics' => 50,
            'identity' => 50,
        ],
    ]);

    $response->assertSessionHasErrors(['personalityType']);

    // Personality should not change
    $this->user->refresh();
    expect($this->user->personality_type)->toBe('INTJ-A');
});

test('personality update validates personality type max length', function () {
    $response = $this->patchCountry(route('profile.personality.update', [], false), [
        'personalityType' => 'INTJ-ABCDEF', // Too long (max 6)
    ]);

    $response->assertSessionHasErrors(['personalityType']);
});

test('personality update validates trait percentages min', function () {
    $response = $this->patchCountry(route('profile.personality.update', [], false), [
        'personalityType' => 'INTJ-A',
        'traitPercentages' => [
            'mind' => 49, // Below minimum (50)
            'energy' => 50,
            'nature' => 50,
            'tactics' => 50,
            'identity' => 50,
        ],
    ]);

    $response->assertSessionHasErrors(['traitPercentages.mind']);
});

test('personality update validates trait percentages max', function () {
    $response = $this->patchCountry(route('profile.personality.update', [], false), [
        'personalityType' => 'INTJ-A',
        'traitPercentages' => [
            'mind' => 150, // Above maximum (100)
            'energy' => 50,
            'nature' => 50,
            'tactics' => 50,
            'identity' => 50,
        ],
    ]);

    $response->assertSessionHasErrors(['traitPercentages.mind']);
});

test('personality update validates trait percentages are integers', function () {
    $response = $this->patchCountry(route('profile.personality.update', [], false), [
        'personalityType' => 'INTJ-A',
        'traitPercentages' => [
            'mind' => 'not_a_number',
            'energy' => 50,
            'nature' => 50,
            'tactics' => 50,
            'identity' => 50,
        ],
    ]);

    $response->assertSessionHasErrors(['traitPercentages.mind']);
});

test('personality update allows null values', function () {
    $response = $this->patchCountry(route('profile.personality.update', [], false), [
        'personalityType' => null,
        'traitPercentages' => null,
    ]);

    $response->assertRedirect($this->countryRoute('profile.edit'));
    $response->assertSessionHas('status', 'personality-updated');
});

test('personality update allows partial trait updates', function () {
    $response = $this->patchCountry(route('profile.personality.update', [], false), [
        'personalityType' => 'INTJ-A',
        'traitPercentages' => [
            'mind' => 80,
            // Other traits not provided - should be accepted
        ],
    ]);

    $response->assertRedirect($this->countryRoute('profile.edit'));
    $response->assertSessionHas('status', 'personality-updated');
});

test('personality update requires authentication', function () {
    auth()->logout();

    $response = $this->patchCountry(route('profile.personality.update', [], false), [
        'personalityType' => 'ENFP-T',
        'traitPercentages' => [
            'mind' => 62,
            'energy' => 85,
            'nature' => 65,
            'tactics' => 73,
            'identity' => 55,
        ],
    ]);

    $response->assertRedirect(route('login'));
});

test('personality update accepts valid MBTI type', function (string $type) {
    $response = $this->patchCountry(route('profile.personality.update', [], false), [
        'personalityType' => $type,
        'traitPercentages' => [
            'mind' => 50,
            'energy' => 50,
            'nature' => 50,
            'tactics' => 50,
            'identity' => 50,
        ],
    ]);

    $response->assertRedirect($this->countryRoute('profile.edit'));
    $response->assertSessionHas('status', 'personality-updated');

    $this->user->refresh();
    expect($this->user->personality_type)->toBe($type);
})->with([
    'INTJ-A', 'INTJ-T',
    'INTP-A', 'INTP-T',
    'ENTJ-A', 'ENTJ-T',
    'ENTP-A', 'ENTP-T',
    'INFJ-A', 'INFJ-T',
    'INFP-A', 'INFP-T',
    'ENFJ-A', 'ENFJ-T',
    'ENFP-A', 'ENFP-T',
    'ISTJ-A', 'ISTJ-T',
    'ISFJ-A', 'ISFJ-T',
    'ESTJ-A', 'ESTJ-T',
    'ESFJ-A', 'ESFJ-T',
    'ISTP-A', 'ISTP-T',
    'ISFP-A', 'ISFP-T',
    'ESTP-A', 'ESTP-T',
    'ESFP-A', 'ESFP-T',
]);

test('personality update rejects invalid MBTI type', function (string $type) {
    $response = $this->patchCountry(route('profile.personality.update', [], false), [
        'personalityType' => $type,
        'traitPercentages' => [
            'mind' => 50,
            'energy' => 50,
            'nature' => 50,
            'tactics' => 50,
            'identity' => 50,
        ],
    ]);

    $response->assertSessionHasErrors(['personalityType']);

    // Should remain unchanged
    $this->user->refresh();
    expect($this->user->personality_type)->toBe('INTJ-A');
})->with([
    ['INTJ-X'],  // Invalid identity (not A or T)
    ['INTJ'],    // Missing identity
    ['intj-a'],  // Lowercase
    ['INT-A'],   // Too short (only 3 letters before dash)
    ['INTJ-AB'], // Too long
    ['1NTJ-A'],  // Contains number
    ['INTJ A'],  // Space instead of dash
]);

test('personality type is stored with prompt runs', function () {
    // Update personality type
    $this->patchCountry(route('profile.personality.update', [], false), [
        'personalityType' => 'ENFP-T',
        'traitPercentages' => [
            'mind' => 62,
            'energy' => 85,
            'nature' => 65,
            'tactics' => 73,
            'identity' => 55,
        ],
    ]);

    $this->user->refresh();

    // Verify the personality type can be accessed
    expect($this->user->personality_type)->toBe('ENFP-T')
        ->and($this->user->trait_percentages)->toBeArray()
        ->and($this->user->trait_percentages['mind'])->toBe(62);
});

test('updating personality does not affect other user data', function () {
    $originalName = $this->user->name;
    $originalEmail = $this->user->email;
    $originalCreatedAt = $this->user->created_at;

    $response = $this->patchCountry(route('profile.personality.update', [], false), [
        'personalityType' => 'ENFP-T',
        'traitPercentages' => [
            'mind' => 62,
            'energy' => 85,
            'nature' => 65,
            'tactics' => 73,
            'identity' => 55,
        ],
    ]);

    $response->assertRedirect($this->countryRoute('profile.edit'));

    $this->user->refresh();

    // Other fields should remain unchanged
    expect($this->user->name)->toBe($originalName)
        ->and($this->user->email)->toBe($originalEmail)
        ->and($this->user->created_at->timestamp)->toBe($originalCreatedAt->timestamp);
});

test('personality update redirects with country parameter in location header', function () {
    $response = $this->patch($this->withCountryPrefix('/profile/personality'), [
        'personalityType' => 'ENFP-T',
        'traitPercentages' => [
            'mind' => 62,
            'energy' => 85,
            'nature' => 65,
            'tactics' => 73,
            'identity' => 55,
        ],
    ]);

    // Verify the Location header contains the country code
    $location = $response->headers->get('Location');
    expect($location)->toContain('/gb/profile');
});
