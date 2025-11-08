<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePersonalityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_user_can_update_personality_type(): void
    {
        $response = $this->patch(route('profile.personality.update'), [
            'personalityType' => 'ENFP-T',
            'traitPercentages' => [
                'mind' => 25,
                'energy' => 85,
                'nature' => 65,
                'tactics' => 40,
                'identity' => 55,
            ],
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'personality-updated');

        $this->user->refresh();
        $this->assertEquals('ENFP-T', $this->user->personality_type);
        $this->assertEquals(25, $this->user->trait_percentages['mind']);
        $this->assertEquals(85, $this->user->trait_percentages['energy']);
    }

    public function test_personality_update_validates_personality_type_format(): void
    {
        $response = $this->patch(route('profile.personality.update'), [
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
        $this->assertEquals('INTJ-A', $this->user->personality_type);
    }

    public function test_personality_update_validates_personality_type_max_length(): void
    {
        $response = $this->patch(route('profile.personality.update'), [
            'personalityType' => 'INTJ-ABCDEF', // Too long (max 6)
        ]);

        $response->assertSessionHasErrors(['personalityType']);
    }

    public function test_personality_update_validates_trait_percentages_min(): void
    {
        $response = $this->patch(route('profile.personality.update'), [
            'personalityType' => 'INTJ-A',
            'traitPercentages' => [
                'mind' => -10, // Below minimum (0)
                'energy' => 50,
                'nature' => 50,
                'tactics' => 50,
                'identity' => 50,
            ],
        ]);

        $response->assertSessionHasErrors(['traitPercentages.mind']);
    }

    public function test_personality_update_validates_trait_percentages_max(): void
    {
        $response = $this->patch(route('profile.personality.update'), [
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
    }

    public function test_personality_update_validates_trait_percentages_are_integers(): void
    {
        $response = $this->patch(route('profile.personality.update'), [
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
    }

    public function test_personality_update_allows_null_values(): void
    {
        $response = $this->patch(route('profile.personality.update'), [
            'personalityType' => null,
            'traitPercentages' => null,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'personality-updated');
    }

    public function test_personality_update_allows_partial_trait_updates(): void
    {
        $response = $this->patch(route('profile.personality.update'), [
            'personalityType' => 'INTJ-A',
            'traitPercentages' => [
                'mind' => 80,
                // Other traits not provided - should be accepted
            ],
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'personality-updated');
    }

    public function test_personality_update_requires_authentication(): void
    {
        auth()->logout();

        $response = $this->patch(route('profile.personality.update'), [
            'personalityType' => 'ENFP-T',
            'traitPercentages' => [
                'mind' => 25,
                'energy' => 85,
                'nature' => 65,
                'tactics' => 40,
                'identity' => 55,
            ],
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_personality_update_accepts_all_valid_mbti_types(): void
    {
        $validTypes = [
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
        ];

        foreach ($validTypes as $type) {
            $response = $this->patch(route('profile.personality.update'), [
                'personalityType' => $type,
                'traitPercentages' => [
                    'mind' => 50,
                    'energy' => 50,
                    'nature' => 50,
                    'tactics' => 50,
                    'identity' => 50,
                ],
            ]);

            $response->assertRedirect(route('profile.edit'));
            $response->assertSessionHas('status', 'personality-updated');

            $this->user->refresh();
            $this->assertEquals($type, $this->user->personality_type);
        }
    }

    public function test_personality_update_rejects_invalid_mbti_types(): void
    {
        // Test patterns that the regex /^[A-Z]{4}-[AT]$/ would reject
        $invalidTypes = [
            'INTJ-X', // Invalid identity (not A or T)
            'INTJ',   // Missing identity
            'intj-a', // Lowercase
            'INT-A',  // Too short (only 3 letters before dash)
            'INTJ-AB', // Too long
            '1NTJ-A', // Contains number
            'INTJ A', // Space instead of dash
        ];

        foreach ($invalidTypes as $type) {
            $response = $this->patch(route('profile.personality.update'), [
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
            $this->assertEquals('INTJ-A', $this->user->personality_type);
        }
    }

    public function test_personality_type_is_stored_with_prompt_runs(): void
    {
        // Update personality type
        $this->patch(route('profile.personality.update'), [
            'personalityType' => 'ENFP-T',
            'traitPercentages' => [
                'mind' => 25,
                'energy' => 85,
                'nature' => 65,
                'tactics' => 40,
                'identity' => 55,
            ],
        ]);

        $this->user->refresh();

        // Verify the personality type can be accessed
        $this->assertEquals('ENFP-T', $this->user->personality_type);
        $this->assertIsArray($this->user->trait_percentages);
        $this->assertEquals(25, $this->user->trait_percentages['mind']);
    }

    public function test_updating_personality_does_not_affect_other_user_data(): void
    {
        $originalName = $this->user->name;
        $originalEmail = $this->user->email;
        $originalCreatedAt = $this->user->created_at;

        $response = $this->patch(route('profile.personality.update'), [
            'personalityType' => 'ENFP-T',
            'traitPercentages' => [
                'mind' => 25,
                'energy' => 85,
                'nature' => 65,
                'tactics' => 40,
                'identity' => 55,
            ],
        ]);

        $response->assertRedirect(route('profile.edit'));

        $this->user->refresh();

        // Other fields should remain unchanged
        $this->assertEquals($originalName, $this->user->name);
        $this->assertEquals($originalEmail, $this->user->email);
        $this->assertEquals($originalCreatedAt->timestamp, $this->user->created_at->timestamp);
    }
}
