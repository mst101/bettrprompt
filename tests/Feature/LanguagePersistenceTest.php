<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguagePersistenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test authenticated user can update language preference
     */
    public function test_authenticated_user_can_update_language(): void
    {
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
        $this->assertEquals('fr-FR', $user->language_code);
        $this->assertTrue($user->language_manually_set);
    }

    /**
     * Test visitor can update language preference
     */
    public function test_visitor_can_update_language(): void
    {
        $visitor = Visitor::factory()->create([
            'language_code' => 'en-GB',
        ]);

        // Test the controller method directly with a created request
        $request = \Illuminate\Http\Request::create('/gb/visitor/language', 'PATCH', [
            'language_code' => 'de-DE',
        ]);

        // Manually set the cookie on the request
        $request->cookies->set('visitor_id', (string) $visitor->id);

        $controller = new \App\Http\Controllers\VisitorController;
        $response = $controller->updateLanguage($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['success' => true], json_decode($response->getContent(), true));

        $visitor->refresh();
        $this->assertEquals('de-DE', $visitor->language_code);
    }

    /**
     * Test language code validation - must be supported
     */
    public function test_language_code_must_be_supported(): void
    {
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
        $this->assertEquals($originalLanguage, $user->language_code);
    }

    /**
     * Test language code is required
     */
    public function test_language_code_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/language', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('language_code');
    }

    /**
     * Test unauthenticated user cannot update profile language
     */
    public function test_unauthenticated_user_cannot_update_profile_language(): void
    {
        $response = $this->patchJsonCountry('/profile/language', [
            'language_code' => 'fr',
        ]);

        // JSON requests return 401 Unauthorized for unauthenticated users
        $response->assertStatus(401);
    }

    /**
     * Test visitor language update without visitor_id cookie succeeds silently
     */
    public function test_visitor_language_update_without_cookie_succeeds_silently(): void
    {
        $response = $this->patchJsonCountry('/visitor/language', [
            'language_code' => 'es',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // No record should be created/updated since there's no visitor_id
    }

    /**
     * Test updating language multiple times
     */
    public function test_user_can_update_language_multiple_times(): void
    {
        $user = User::factory()->create(['language_code' => 'en-GB']);

        // First update
        $this->actingAs($user)
            ->patchJsonCountry('/profile/language', ['language_code' => 'fr-FR']);
        $user->refresh();
        $this->assertEquals('fr-FR', $user->language_code);

        // Second update
        $this->actingAs($user)
            ->patchJsonCountry('/profile/language', ['language_code' => 'de-DE']);
        $user->refresh();
        $this->assertEquals('de-DE', $user->language_code);

        // Third update
        $this->actingAs($user)
            ->patchJsonCountry('/profile/language', ['language_code' => 'es-ES']);
        $user->refresh();
        $this->assertEquals('es-ES', $user->language_code);
    }

    /**
     * Test supported languages can be set
     */
    public function test_supported_languages_can_be_set(): void
    {
        $user = User::factory()->create();
        $supportedLanguages = ['en-GB', 'en-US', 'de-DE', 'fr-FR', 'es-ES'];

        foreach ($supportedLanguages as $language) {
            $response = $this->actingAs($user)
                ->patchJsonCountry('/profile/language', [
                    'language_code' => $language,
                ]);

            $response->assertStatus(200);
            $user->refresh();
            $this->assertEquals($language, $user->language_code);
        }
    }

    /**
     * Test language_manually_set flag is set when user updates language
     */
    public function test_language_manually_set_flag_is_updated(): void
    {
        $user = User::factory()->create([
            'language_code' => 'en-US',
            'language_manually_set' => false,
        ]);

        $this->actingAs($user)
            ->patchJsonCountry('/profile/language', [
                'language_code' => 'fr',
            ]);

        $user->refresh();
        $this->assertTrue($user->language_manually_set);
    }

    /**
     * Test language persists in database for subsequent requests
     */
    public function test_language_persists_in_database(): void
    {
        $user = User::factory()->create(['language_code' => 'en-GB']);

        // Update language
        $this->actingAs($user)
            ->patchJsonCountry('/profile/language', [
                'language_code' => 'fr-FR',
            ]);

        // Fetch fresh instance from database
        $freshUser = User::find($user->id);
        $this->assertEquals('fr-FR', $freshUser->language_code);
    }

    /**
     * Test language code is case-sensitive in validation
     */
    public function test_language_code_validation_is_case_sensitive(): void
    {
        $user = User::factory()->create();

        // en-gb (lowercase) should not match en-GB (configured case)
        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/language', [
                'language_code' => 'en-gb',
            ]);

        // Should fail due to case sensitivity
        $response->assertStatus(422);
    }

    /**
     * Test language code with maximum length
     */
    public function test_language_code_max_length_validation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJsonCountry('/profile/language', [
                'language_code' => 'this-is-way-too-long-for-a-locale-code',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('language_code');
    }
}
