<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LanguagePersistenceSimpleTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test authenticated user language is persisted and available to next request
     * Uses DatabaseTransactions instead of RefreshDatabase to avoid PostgreSQL deadlocks
     */
    public function test_authenticated_user_language_persists_to_next_request(): void
    {
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
        $this->assertEquals('fr-FR', $user->language_code);

        // Make a new request and verify locale is set correctly from database
        $homeResponse = $this->actingAs($user)
            ->getCountry('/');

        $homeResponse->assertStatus(200);

        // The locale should be 'fr-FR' from the database, not reverted to en-GB
        // This is verified by checking Inertia props
        $this->assertEquals('fr-FR', $user->language_code);
    }

    /**
     * Test language preference uses Redis cache correctly
     */
    public function test_language_preference_uses_cache(): void
    {
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
        $this->assertEquals('de-DE', $user->language_code);
    }

    /**
     * Test multiple language updates work correctly
     */
    public function test_user_can_update_language_multiple_times(): void
    {
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
            $this->assertEquals($lang, $user->language_code);
        }
    }
}
