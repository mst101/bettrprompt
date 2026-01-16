<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can update display mode preference
     */
    public function test_authenticated_user_can_update_display_mode_preference(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson(
            route('api.user.preferences.update'),
            ['question_display_mode' => 'show-all']
        );

        $response->assertOk();
        $response->assertJson(['message' => 'Preferences updated successfully']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'question_display_mode' => 'show-all',
        ]);
    }

    /**
     * Test that authenticated user preference persists
     */
    public function test_authenticated_user_preference_persists(): void
    {
        $user = User::factory()->create(['question_display_mode' => 'one-at-a-time']);

        $this->actingAs($user)->patchJson(
            route('api.user.preferences.update'),
            ['question_display_mode' => 'show-all']
        );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'question_display_mode' => 'show-all',
        ]);

        // Refresh and verify
        $user->refresh();
        $this->assertEquals('show-all', $user->question_display_mode);
    }

    /**
     * Test that endpoint accepts requests without authentication
     */
    public function test_endpoint_accepts_unauthenticated_requests(): void
    {
        $response = $this->patchJson(
            route('api.user.preferences.update'),
            ['question_display_mode' => 'show-all']
        );

        // Should not require authentication
        $response->assertOk();
        $response->assertJson(['message' => 'Preferences updated successfully']);
    }

    /**
     * Test that request without visitor_id cookie returns 200 (but doesn't update anything)
     */
    public function test_guest_without_visitor_cookie_returns_ok(): void
    {
        $response = $this->patchJson(
            route('api.user.preferences.update'),
            ['question_display_mode' => 'show-all']
        );

        // Should return 200 OK even if no visitor_id cookie present
        $response->assertOk();
    }

    /**
     * Test that invalid visitor_id cookie doesn't crash
     */
    public function test_invalid_visitor_id_cookie_handled_gracefully(): void
    {
        $response = $this->withCookie('visitor_id', 'invalid-not-a-uuid')
            ->patchJson(
                route('api.user.preferences.update'),
                ['question_display_mode' => 'show-all']
            );

        // Should still return 200, just won't save since visitor doesn't exist
        $response->assertOk();
    }
}
