<?php

use App\Models\User;

/**
 * Unit tests for User model business logic
 * These test the model methods directly without HTTP layer
 */
describe('User profile management', function () {
    test('updateLocation sets all location fields and triggers profile completion', function () {
        $user = User::factory()->create([
            'profile_completion_percentage' => 0,
        ]);

        $locationData = [
            'country_code' => 'gb',
            'region' => 'England',
            'city' => 'London',
            'timezone' => 'Europe/London',
            'currency_code' => 'GBP',
            'language_code' => 'en-GB',
        ];

        $user->updateLocation($locationData);

        expect($user->fresh())
            ->country_code->toBe('gb')
            ->region->toBe('England')
            ->city->toBe('London')
            ->timezone->toBe('Europe/London')
            ->currency_code->toBe('GBP')
            ->language_code->toBe('en-GB')
            ->location_manually_set->toBeTrue()
            ->profile_completion_percentage->toBeGreaterThan(0);
    });

    test('updateProfessional updates all professional fields', function () {
        $user = User::factory()->create();

        $professionalData = [
            'job_title' => 'Senior Developer',
            'industry' => 'Technology',
            'experience_level' => 'senior',
            'company_size' => 'medium',
        ];

        $user->updateProfessional($professionalData);

        expect($user->fresh())
            ->job_title->toBe('Senior Developer')
            ->industry->toBe('Technology')
            ->experience_level->toBe('senior')
            ->company_size->toBe('medium');
    });

    test('clearLocation removes all location fields and updates profile completion', function () {
        $user = User::factory()->create([
            'country_code' => 'gb',
            'region' => 'England',
            'city' => 'London',
            'timezone' => 'Europe/London',
            'currency_code' => 'GBP',
            'language_code' => 'en-GB',
            'location_manually_set' => true,
            'profile_completion_percentage' => 50,
        ]);

        $user->clearLocation();

        expect($user->fresh())
            ->country_code->toBeNull()
            ->region->toBeNull()
            ->city->toBeNull()
            ->timezone->toBeNull()
            ->currency_code->toBeNull()
            ->language_code->toBeNull()
            ->location_manually_set->toBeFalse()
            ->profile_completion_percentage->toBeLessThan(50);
    });

    test('updateTools updates preferred tools and primary language', function () {
        $user = User::factory()->create();

        $toolsData = [
            'preferred_tools' => ['VSCode', 'Git', 'Docker'],
            'primary_programming_language' => 'PHP',
        ];

        $user->updateTools($toolsData);

        expect($user->fresh())
            ->preferred_tools->toBe(['VSCode', 'Git', 'Docker'])
            ->primary_programming_language->toBe('PHP');
    });

    test('multiple profile updates correctly accumulate completion percentage', function () {
        $user = User::factory()->create([
            'profile_completion_percentage' => 0,
        ]);

        $user->updateLocation([
            'country_code' => 'gb',
            'region' => 'England',
            'city' => 'London',
            'timezone' => 'Europe/London',
            'currency_code' => 'GBP',
            'language_code' => 'en-GB',
        ]);

        $completionAfterLocation = $user->fresh()->profile_completion_percentage;

        $user->updateProfessional([
            'job_title' => 'Developer',
            'industry' => 'Tech',
            'experience_level' => 'mid',
            'company_size' => 'small',
        ]);

        $completionAfterProfessional = $user->fresh()->profile_completion_percentage;

        expect($completionAfterProfessional)->toBeGreaterThan($completionAfterLocation);
    });
});

describe('User referral code', function () {
    test('getReferralCode generates code if none exists', function () {
        $user = User::factory()->create(['referral_code' => null]);

        $code = $user->getReferralCode();

        expect($code)
            ->toBeString()
            ->toHaveLength(8)
            ->and($user->fresh()->referral_code)->toBe($code);

    });

    test('getReferralCode returns existing code without regenerating', function () {
        $user = User::factory()->create(['referral_code' => 'EXISTING']);

        $code = $user->getReferralCode();

        expect($code)->toBe('EXISTING');
    });

    test('generateReferralCode creates unique codes', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $code1 = $user1->generateReferralCode();
        $code2 = $user2->generateReferralCode();

        expect($code1)->not->toBe($code2);
    });
});

describe('User context building', function () {
    test('getUserContext returns complete context for fully profiled user', function () {
        $user = User::factory()->create([
            'country_code' => 'gb',
            'timezone' => 'Europe/London',
            'currency_code' => 'GBP',
            'job_title' => 'Developer',
            'industry' => 'Technology',
            'team_size' => 'small',
            'budget_consciousness' => 'mixed',
            'preferred_tools' => ['VSCode', 'Git'],
            'personality_type' => 'INTJ-A',
            'trait_percentages' => ['mind' => 65, 'energy' => 70],
        ]);

        $context = $user->getUserContext();

        expect($context)
            ->toHaveKeys(['location', 'professional', 'team', 'preferences', 'personality'])
            ->and($context['location']['country_code'])->toBe('gb')
            ->and($context['professional']['job_title'])->toBe('Developer')
            ->and($context['preferences']['budget_consciousness'])->toBe('mixed')
            ->and($context['personality']['type'])->toBe('INTJ-A');
    });

    test('getUserContext handles missing optional fields gracefully', function () {
        $user = User::factory()->create([
            'country_code' => null,
            'job_title' => null,
        ]);

        $context = $user->getUserContext();

        expect($context)
            ->toHaveKeys(['location', 'professional', 'team', 'preferences', 'personality'])
            ->and($context['location']['country_code'])->toBeNull()
            ->and($context['professional']['job_title'])->toBeNull();
    });
});
