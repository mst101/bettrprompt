<?php

use App\Models\User;

describe('Country Code Validation', function () {
    test('accepts valid country codes', function () {
        $user = User::factory()->create();

        $validCodes = ['GB', 'US', 'DE', 'FR', 'ES', 'JP'];

        foreach ($validCodes as $code) {
            $response = $this->actingAs($user)
                ->patchJsonCountry('/profile/location', [
                    'country_code' => $code,
                ]);

            $response->assertStatus(200);
        }
    });

    test('rejects country code with wrong length', function () {
        $user = User::factory()->create();

        // Too short
        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'country_code' => 'G',
            ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country_code']);

        // Too long
        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'country_code' => 'GBR',
            ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country_code']);
    });

    test('accepts null country_code', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'country_code' => null,
            ]);

        $response->assertStatus(200);
    });

    test('rejects non-string country_code', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'country_code' => 123,
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['country_code']);
    });
});

describe('Region Validation', function () {
    test('accepts valid region names', function () {
        $user = User::factory()->create();

        $validRegions = ['California', 'Bavaria', 'Ile-de-France', 'Madrid'];

        foreach ($validRegions as $region) {
            $response = $this->actingAs($user)
                ->patchJsonCountry('/profile/location', [
                    'region' => $region,
                ]);

            $response->assertStatus(200);
        }
    });

    test('validates region maximum length is 100 characters', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'region' => str_repeat('a', 101),
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['region']);
    });

    test('accepts region at maximum length', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'region' => str_repeat('a', 100),
            ]);

        $response->assertStatus(200);
    });

    test('accepts null region', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'region' => null,
            ]);

        $response->assertStatus(200);
    });
});

describe('City Validation', function () {
    test('accepts valid city names', function () {
        $user = User::factory()->create();

        $validCities = ['London', 'New York', 'Paris', 'Berlin', 'Tokyo'];

        foreach ($validCities as $city) {
            $response = $this->actingAs($user)
                ->patchJsonCountry('/profile/location', [
                    'city' => $city,
                ]);

            $response->assertStatus(200);
        }
    });

    test('validates city maximum length is 100 characters', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'city' => str_repeat('a', 101),
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['city']);
    });

    test('accepts null city', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'city' => null,
            ]);

        $response->assertStatus(200);
    });
});

describe('Timezone Validation', function () {
    test('accepts valid timezone names', function () {
        $user = User::factory()->create();

        $validTimezones = [
            'Europe/London',
            'America/New_York',
            'Europe/Paris',
            'Asia/Tokyo',
            'UTC',
        ];

        foreach ($validTimezones as $timezone) {
            $response = $this->actingAs($user)
                ->patchJsonCountry('/profile/location', [
                    'timezone' => $timezone,
                ]);

            $response->assertStatus(200);
        }
    });

    test('accepts null timezone', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'timezone' => null,
            ]);

        $response->assertStatus(200);
    });
});

describe('Currency Code Validation', function () {
    test('accepts valid currency codes', function () {
        $user = User::factory()->create();

        $validCodes = ['GBP', 'USD', 'EUR', 'JPY', 'CHF'];

        foreach ($validCodes as $code) {
            $response = $this->actingAs($user)
                ->patchJsonCountry('/profile/location', [
                    'currency_code' => $code,
                ]);

            $response->assertStatus(200);
        }
    });

    test('rejects currency code with wrong length', function () {
        $user = User::factory()->create();

        // Too short
        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'currency_code' => 'GB',
            ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['currency_code']);

        // Too long
        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'currency_code' => 'GBPX',
            ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['currency_code']);
    });

    test('accepts null currency_code', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'currency_code' => null,
            ]);

        $response->assertStatus(200);
    });
});

describe('Language Code Validation', function () {
    test('accepts valid language codes', function () {
        $user = User::factory()->create();

        $validCodes = ['en-GB', 'en-US', 'de-DE', 'fr-FR', 'es-ES'];

        foreach ($validCodes as $code) {
            $response = $this->actingAs($user)
                ->patchJsonCountry('/profile/location', [
                    'language_code' => $code,
                ]);

            $response->assertStatus(200);
        }
    });

    test('validates language code maximum length is 5 characters', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'language_code' => 'en-GBX', // 6 characters
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['language_code']);
    });

    test('accepts language code at maximum length', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'language_code' => 'en-GB',
            ]);

        $response->assertStatus(200);
    });

    test('accepts null language_code', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'language_code' => null,
            ]);

        $response->assertStatus(200);
    });
});

describe('Integration Tests', function () {
    test('accepts complete valid location form', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'country_code' => 'DE',
                'region' => 'Bavaria',
                'city' => 'Munich',
                'timezone' => 'Europe/Berlin',
                'currency_code' => 'EUR',
                'language_code' => 'de-DE',
            ]);

        $response->assertStatus(200);

        $user->refresh();
        expect($user->country_code)->toBe('DE');
        expect($user->region)->toBe('Bavaria');
        expect($user->city)->toBe('Munich');
        expect($user->timezone)->toBe('Europe/Berlin');
        expect($user->currency_code)->toBe('EUR');
        expect($user->language_code)->toBe('de-DE');
    });

    test('accepts partial location update', function () {
        $user = User::factory()->create([
            'country_code' => 'GB',
            'region' => 'England',
            'city' => 'London',
        ]);

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'city' => 'Manchester',
            ]);

        $response->assertStatus(200);

        $user->refresh();
        expect($user->country_code)->toBe('GB'); // Unchanged
        expect($user->region)->toBe('England'); // Unchanged
        expect($user->city)->toBe('Manchester'); // Updated
    });

    test('rejects unauthenticated access', function () {
        $response = $this->patchJsonCountry('/profile/location', [
            'country_code' => 'GB',
        ]);

        $response->assertStatus(401);
    });

    test('accepts all null values to clear location', function () {
        $user = User::factory()->create([
            'country_code' => 'GB',
            'region' => 'England',
            'city' => 'London',
        ]);

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/location', [
                'country_code' => null,
                'region' => null,
                'city' => null,
                'timezone' => null,
                'currency_code' => null,
                'language_code' => null,
            ]);

        $response->assertStatus(200);

        $user->refresh();
        expect($user->country_code)->toBeNull();
        expect($user->region)->toBeNull();
        expect($user->city)->toBeNull();
    });
});
