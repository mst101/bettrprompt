<?php

use App\Models\PromptRun;
use App\Models\User;
use App\Models\Visitor;

/**
 * Unit tests for Visitor model business logic
 */
describe('Visitor conversion status', function () {
    test('hasConverted returns true when visitor has user_id and converted_at', function () {
        $user = User::factory()->create();
        $visitor = Visitor::factory()->create([
            'user_id' => $user->id,
            'converted_at' => now(),
        ]);

        expect($visitor->hasConverted())->toBeTrue();
    });

    test('hasConverted returns false when user_id is null', function () {
        $visitor = Visitor::factory()->create([
            'user_id' => null,
            'converted_at' => now(),
        ]);

        expect($visitor->hasConverted())->toBeFalse();
    });

    test('hasConverted returns false when converted_at is null', function () {
        $user = User::factory()->create();
        $visitor = Visitor::factory()->create([
            'user_id' => $user->id,
            'converted_at' => null,
        ]);

        expect($visitor->hasConverted())->toBeFalse();
    });

    test('hasConverted returns false when both user_id and converted_at are null', function () {
        $visitor = Visitor::factory()->create([
            'user_id' => null,
            'converted_at' => null,
        ]);

        expect($visitor->hasConverted())->toBeFalse();
    });
});

describe('Visitor return status', function () {
    test('isReturning returns true when visitor has visited more than 1 hour apart', function () {
        $firstVisit = now()->subHours(2);
        $visitor = Visitor::factory()->create([
            'first_visit_at' => $firstVisit,
            'last_visit_at' => now(),
        ]);

        expect($visitor->isReturning())->toBeTrue();
    });

    test('isReturning returns false when first and last visit are within 1 hour', function () {
        $firstVisit = now()->subMinutes(30);
        $visitor = Visitor::factory()->create([
            'first_visit_at' => $firstVisit,
            'last_visit_at' => now(),
        ]);

        expect($visitor->isReturning())->toBeFalse();
    });

    test('isReturning returns false when first_visit_at is null', function () {
        $visitor = Visitor::factory()->create([
            'first_visit_at' => null,
            'last_visit_at' => now(),
        ]);

        expect($visitor->isReturning())->toBeFalse();
    });

    test('isReturning returns true when first and last visit are days apart', function () {
        $firstVisit = now()->subDays(7);
        $visitor = Visitor::factory()->create([
            'first_visit_at' => $firstVisit,
            'last_visit_at' => now(),
        ]);

        expect($visitor->isReturning())->toBeTrue();
    });
});

describe('Visitor prompt completion status', function () {
    test('hasCompletedPrompts returns true when visitor has completed prompt runs', function () {
        $visitor = Visitor::factory()->create();
        PromptRun::factory()->create([
            'visitor_id' => $visitor->id,
            'workflow_stage' => '2_completed',
            'optimized_prompt' => 'Test optimised prompt',
        ]);

        expect($visitor->hasCompletedPrompts())->toBeTrue();
    });

    test('hasCompletedPrompts returns false when visitor has no prompt runs', function () {
        $visitor = Visitor::factory()->create();

        expect($visitor->hasCompletedPrompts())->toBeFalse();
    });

    test('hasCompletedPrompts returns false when prompt runs are not completed', function () {
        $visitor = Visitor::factory()->create();
        PromptRun::factory()->create([
            'visitor_id' => $visitor->id,
            'workflow_stage' => '1_processing',
            'optimized_prompt' => null,
        ]);

        expect($visitor->hasCompletedPrompts())->toBeFalse();
    });

    test('hasCompletedPrompts returns false when completed runs have null optimized_prompt', function () {
        $visitor = Visitor::factory()->create();
        PromptRun::factory()->create([
            'visitor_id' => $visitor->id,
            'workflow_stage' => '2_completed',
            'optimized_prompt' => null,
        ]);

        expect($visitor->hasCompletedPrompts())->toBeFalse();
    });

    test('hasCompletedPrompts returns true with multiple completed runs', function () {
        $visitor = Visitor::factory()->create();
        PromptRun::factory()->count(3)->create([
            'visitor_id' => $visitor->id,
            'workflow_stage' => '2_completed',
            'optimized_prompt' => 'Test prompt',
        ]);

        expect($visitor->hasCompletedPrompts())->toBeTrue();
    });

    test('hasCompletedPrompts ignores other visitors prompt runs', function () {
        $visitor1 = Visitor::factory()->create();
        $visitor2 = Visitor::factory()->create();

        PromptRun::factory()->create([
            'visitor_id' => $visitor2->id,
            'workflow_stage' => '2_completed',
            'optimized_prompt' => 'Test prompt',
        ]);

        expect($visitor1->hasCompletedPrompts())->toBeFalse();
    });
});

describe('Visitor location data', function () {
    test('hasLocationData returns true when country_code and timezone are set', function () {
        $visitor = Visitor::factory()->create([
            'country_code' => 'GB',
            'timezone' => 'Europe/London',
        ]);

        expect($visitor->hasLocationData())->toBeTrue();
    });

    test('hasLocationData returns false when country_code is null', function () {
        $visitor = Visitor::factory()->create([
            'country_code' => null,
            'timezone' => 'Europe/London',
        ]);

        expect($visitor->hasLocationData())->toBeFalse();
    });

    test('hasLocationData returns false when timezone is null', function () {
        $visitor = Visitor::factory()->create([
            'country_code' => 'GB',
            'timezone' => null,
        ]);

        expect($visitor->hasLocationData())->toBeFalse();
    });

    test('hasLocationData returns false when both are null', function () {
        $visitor = Visitor::factory()->create([
            'country_code' => null,
            'timezone' => null,
        ]);

        expect($visitor->hasLocationData())->toBeFalse();
    });
});

describe('Visitor location summary', function () {
    test('getLocationSummary returns full city, region, country when all available', function () {
        $visitor = Visitor::factory()->create([
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
            'region' => 'England',
            'city' => 'London',
            'timezone' => 'Europe/London',
        ]);

        expect($visitor->getLocationSummary())->toBe('London, England, United Kingdom');
    });

    test('getLocationSummary returns region and country when city is null', function () {
        $visitor = Visitor::factory()->create([
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
            'region' => 'England',
            'city' => null,
            'timezone' => 'Europe/London',
        ]);

        expect($visitor->getLocationSummary())->toBe('England, United Kingdom');
    });

    test('getLocationSummary returns Unknown location when country_code is null', function () {
        $visitor = Visitor::factory()->create([
            'country_code' => null,
            'country_name' => 'United Kingdom',
            'region' => 'England',
            'city' => 'London',
            'timezone' => 'Europe/London',
        ]);

        expect($visitor->getLocationSummary())->toBe('Unknown location');
    });

    test('getLocationSummary returns Unknown location when timezone is null', function () {
        $visitor = Visitor::factory()->create([
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
            'region' => 'England',
            'city' => 'London',
            'timezone' => null,
        ]);

        expect($visitor->getLocationSummary())->toBe('Unknown location');
    });

    test('getLocationSummary returns Unknown location when no location data', function () {
        $visitor = Visitor::factory()->create([
            'country_code' => null,
            'timezone' => null,
        ]);

        expect($visitor->getLocationSummary())->toBe('Unknown location');
    });
});
