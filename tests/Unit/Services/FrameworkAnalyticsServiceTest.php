<?php

use App\Models\FrameworkAnalytic;
use App\Models\PromptRun;
use App\Models\User;
use App\Models\Visitor;
use App\Services\FrameworkAnalyticsService;

beforeEach(function () {
    $this->service = app(FrameworkAnalyticsService::class);
    $this->user = User::factory()->create();
    $this->visitor = Visitor::factory()->create();
});

describe('Framework Analytics Service - Selection Recording', function () {
    test('records framework selection with all required fields', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        $selection = $this->service->recordSelection(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'socratic',
            chosenFramework: 'socratic'
        );

        expect($selection)->toBeInstanceOf(FrameworkAnalytic::class)
            ->and($selection->prompt_run_id)->toBe($promptRun->id)
            ->and($selection->recommended_framework)->toBe('socratic')
            ->and($selection->chosen_framework)->toBe('socratic')
            ->and($selection->accepted_recommendation)->toBeTrue()
            ->and($selection->selected_at)->not->toBeNull();
    });

    test('records accepted recommendation when frameworks match', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        $selection = $this->service->recordSelection(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'stoic',
            chosenFramework: 'stoic'
        );

        expect($selection->accepted_recommendation)->toBeTrue();
    });

    test('records rejected recommendation when frameworks differ', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        $selection = $this->service->recordSelection(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'socratic',
            chosenFramework: 'stoic'
        );

        expect($selection->accepted_recommendation)->toBeFalse();
    });

    test('records selection with task category and personality type', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        $selection = $this->service->recordSelection(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'system-1',
            chosenFramework: 'system-1',
            taskCategory: 'brainstorming',
            personalityType: 'INTJ'
        );

        expect($selection->task_category)->toBe('brainstorming')
            ->and($selection->personality_type)->toBe('INTJ');
    });

    test('records selection with recommendation scores', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $scores = [
            'socratic' => 0.92,
            'stoic' => 0.78,
            'system-1' => 0.65,
        ];

        $selection = $this->service->recordSelection(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'socratic',
            chosenFramework: 'socratic',
            recommendationScores: $scores
        );

        expect($selection->recommendation_scores)->toBe($scores);
    });

    test('records multiple selections for different prompt runs', function () {
        $promptRun1 = PromptRun::factory()->for($this->user)->create();
        $promptRun2 = PromptRun::factory()->for($this->user)->create();

        $selection1 = $this->service->recordSelection(
            promptRun: $promptRun1,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'socratic',
            chosenFramework: 'socratic'
        );

        $selection2 = $this->service->recordSelection(
            promptRun: $promptRun2,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'stoic',
            chosenFramework: 'stoic'
        );

        expect(FrameworkAnalytic::count())->toBe(2);
    });
});

describe('Framework Analytics Service - Framework Updates', function () {
    test('updates chosen framework after initial selection', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        $selection = $this->service->recordSelection(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'socratic',
            chosenFramework: 'socratic'
        );

        $updated = $this->service->updateChosenFramework($selection, 'stoic');

        expect($updated->chosen_framework)->toBe('stoic')
            ->and($updated->accepted_recommendation)->toBeFalse();
    });

    test('recalculates acceptance when framework changed to match recommendation', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        $selection = $this->service->recordSelection(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'socratic',
            chosenFramework: 'stoic'
        );

        expect($selection->accepted_recommendation)->toBeFalse();

        $updated = $this->service->updateChosenFramework($selection, 'socratic');

        expect($updated->accepted_recommendation)->toBeTrue();
    });

    test('preserves other fields when updating chosen framework', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        $selection = $this->service->recordSelection(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'socratic',
            chosenFramework: 'socratic',
            taskCategory: 'research'
        );

        $updated = $this->service->updateChosenFramework($selection, 'stoic');

        expect($updated->task_category)->toBe('research');
    });
});

describe('Framework Analytics Service - Outcome Tracking', function () {
    test('updates selection with prompt rating', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $selection = $this->service->recordSelection(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'socratic',
            chosenFramework: 'socratic'
        );

        $updated = $this->service->updateWithOutcome($selection, promptRating: 5);

        expect($updated->prompt_rating)->toBe(5);
    });

    test('updates selection with rating explanation', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $selection = $this->service->recordSelection(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'socratic',
            chosenFramework: 'socratic'
        );

        $updated = $this->service->updateWithOutcome(
            $selection,
            promptRating: 4,
            ratingExplanation: 'Very helpful framework!'
        );

        expect($updated->rating_explanation)->toBe('Very helpful framework!');
    });

    test('updates selection with copy and edit flags', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $selection = $this->service->recordSelection(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'socratic',
            chosenFramework: 'socratic'
        );

        $updated = $this->service->updateWithOutcome(
            $selection,
            promptCopied: true,
            promptEdited: true,
            editPercentage: 25.5
        );

        expect($updated->prompt_copied)->toBeTrue()
            ->and($updated->prompt_edited)->toBeTrue()
            ->and($updated->edit_percentage)->toBe(25.5);
    });

    test('updates selection with all outcome metrics', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $selection = $this->service->recordSelection(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'socratic',
            chosenFramework: 'socratic'
        );

        $updated = $this->service->updateWithOutcome(
            $selection,
            promptRating: 5,
            ratingExplanation: 'Excellent!',
            promptCopied: true,
            promptEdited: true,
            editPercentage: 10.0
        );

        expect($updated->prompt_rating)->toBe(5)
            ->and($updated->rating_explanation)->toBe('Excellent!')
            ->and($updated->prompt_copied)->toBeTrue()
            ->and($updated->prompt_edited)->toBeTrue()
            ->and($updated->edit_percentage)->toBe(10.0);
    });

    test('preserves existing outcome data when updating one field', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $selection = $this->service->recordSelection(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'socratic',
            chosenFramework: 'socratic'
        );

        // Set multiple fields
        $this->service->updateWithOutcome(
            $selection,
            promptRating: 5,
            promptCopied: true,
            promptEdited: true,
            editPercentage: 20.0
        );

        // Update only rating
        $updated = $this->service->updateWithOutcome(
            $selection->fresh(),
            promptRating: 3
        );

        expect($updated->prompt_rating)->toBe(3)
            ->and($updated->prompt_copied)->toBeTrue()
            ->and($updated->prompt_edited)->toBeTrue()
            ->and($updated->edit_percentage)->toBe(20.0);
    });
});

describe('Framework Analytics Service - Acceptance Rate', function () {
    test('calculates acceptance rate correctly', function () {
        // Create 10 selections: 7 accepted, 3 rejected for 'socratic'
        for ($i = 0; $i < 10; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();

            $chosenFramework = $i < 7 ? 'socratic' : 'stoic';
            $this->service->recordSelection(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                recommendedFramework: 'socratic',
                chosenFramework: $chosenFramework
            );
        }

        $rate = $this->service->getAcceptanceRate('socratic');

        expect($rate)->toBe(70.0);
    });

    test('returns 0 acceptance rate for no recommendations', function () {
        $rate = $this->service->getAcceptanceRate('nonexistent');

        expect($rate)->toBe(0.0);
    });

    test('returns 100 acceptance rate when all accepted', function () {
        for ($i = 0; $i < 5; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();

            $this->service->recordSelection(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                recommendedFramework: 'stoic',
                chosenFramework: 'stoic'
            );
        }

        $rate = $this->service->getAcceptanceRate('stoic');

        expect($rate)->toBe(100.0);
    });

    test('returns 0 acceptance rate when all rejected', function () {
        for ($i = 0; $i < 4; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();

            $this->service->recordSelection(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                recommendedFramework: 'socratic',
                chosenFramework: 'stoic'
            );
        }

        $rate = $this->service->getAcceptanceRate('socratic');

        expect($rate)->toBe(0.0);
    });
});

describe('Framework Analytics Service - Rating Calculations', function () {
    test('calculates average rating for framework', function () {
        // Create 4 selections with different ratings
        $ratings = [5, 4, 3, 2];

        foreach ($ratings as $rating) {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $selection = $this->service->recordSelection(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                recommendedFramework: 'socratic',
                chosenFramework: 'socratic'
            );

            $this->service->updateWithOutcome($selection, promptRating: $rating);
        }

        $average = $this->service->getAverageRating('socratic');

        expect($average)->toBe(3.5);
    });

    test('ignores unrated selections in average', function () {
        // Create 2 rated and 2 unrated selections
        for ($i = 0; $i < 2; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $selection = $this->service->recordSelection(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                recommendedFramework: 'socratic',
                chosenFramework: 'socratic'
            );

            $this->service->updateWithOutcome($selection, promptRating: 5);
        }

        for ($i = 0; $i < 2; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $this->service->recordSelection(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                recommendedFramework: 'socratic',
                chosenFramework: 'socratic'
            );
        }

        $average = $this->service->getAverageRating('socratic');

        expect($average)->toBe(5.0);
    });

    test('returns null average when no ratings exist', function () {
        $average = $this->service->getAverageRating('nonexistent');

        expect($average)->toBeNull();
    });
});

describe('Framework Analytics Service - Copy Rate', function () {
    test('calculates copy rate correctly', function () {
        // Create 10 selections: 6 copied, 4 not copied for 'socratic'
        for ($i = 0; $i < 10; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $selection = $this->service->recordSelection(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                recommendedFramework: 'socratic',
                chosenFramework: 'socratic'
            );

            if ($i < 6) {
                $this->service->updateWithOutcome($selection, promptCopied: true);
            }
        }

        $rate = $this->service->getCopyRate('socratic');

        expect($rate)->toBe(60.0);
    });

    test('returns 0 copy rate for no selections', function () {
        $rate = $this->service->getCopyRate('nonexistent');

        expect($rate)->toBe(0.0);
    });

    test('returns 100 copy rate when all copied', function () {
        for ($i = 0; $i < 3; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $selection = $this->service->recordSelection(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                recommendedFramework: 'stoic',
                chosenFramework: 'stoic'
            );

            $this->service->updateWithOutcome($selection, promptCopied: true);
        }

        $rate = $this->service->getCopyRate('stoic');

        expect($rate)->toBe(100.0);
    });

    test('returns 0 copy rate when none copied', function () {
        for ($i = 0; $i < 4; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $this->service->recordSelection(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                recommendedFramework: 'socratic',
                chosenFramework: 'socratic'
            );
        }

        $rate = $this->service->getCopyRate('socratic');

        expect($rate)->toBe(0.0);
    });
});

describe('Framework Analytics Service - Performance Analysis', function () {
    test('gets framework performance summary', function () {
        // Create 5 socratic selections: 4 accepted, 1 rejected
        for ($i = 0; $i < 5; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $chosenFramework = $i < 4 ? 'socratic' : 'stoic';

            $selection = $this->service->recordSelection(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                recommendedFramework: 'socratic',
                chosenFramework: $chosenFramework
            );

            if ($i < 3) {
                $this->service->updateWithOutcome($selection, promptRating: 5);
            }
        }

        $performance = $this->service->getFrameworkPerformance('socratic');

        expect($performance)
            ->toHaveKeys(['framework', 'total_recommended', 'total_chosen', 'acceptance_rate', 'average_rating', 'copy_rate'])
            ->and($performance['framework'])->toBe('socratic')
            ->and($performance['total_recommended'])->toBe(5)
            ->and($performance['acceptance_rate'])->toBe(80.0);
    });

    test('gets top frameworks by acceptance rate', function () {
        // Create socratic with 80% acceptance
        for ($i = 0; $i < 5; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $chosenFramework = $i < 4 ? 'socratic' : 'stoic';

            $this->service->recordSelection(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                recommendedFramework: 'socratic',
                chosenFramework: $chosenFramework
            );
        }

        // Create stoic with 100% acceptance
        for ($i = 0; $i < 3; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();

            $this->service->recordSelection(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                recommendedFramework: 'stoic',
                chosenFramework: 'stoic'
            );
        }

        $topFrameworks = $this->service->getTopFrameworks(2);

        expect($topFrameworks)->toHaveCount(2);
        expect($topFrameworks[0]['framework'])->toBe('stoic');
        expect($topFrameworks[0]['acceptance_rate'])->toBe(100.0);
        expect($topFrameworks[1]['framework'])->toBe('socratic');
        expect($topFrameworks[1]['acceptance_rate'])->toBe(80.0);
    });

    test('limits top frameworks result', function () {
        // Create 5 different frameworks
        for ($j = 0; $j < 5; $j++) {
            $framework = "framework-$j";
            for ($i = 0; $i < 2; $i++) {
                $promptRun = PromptRun::factory()->for($this->user)->create();

                $this->service->recordSelection(
                    promptRun: $promptRun,
                    visitorId: $this->visitor->id,
                    userId: $this->user->id,
                    recommendedFramework: $framework,
                    chosenFramework: $framework
                );
            }
        }

        $topFrameworks = $this->service->getTopFrameworks(3);

        expect($topFrameworks)->toHaveCount(3);
    });
});

describe('Framework Analytics Service - Data Integrity', function () {
    test('does not affect other frameworks when recording selection', function () {
        // Create 2 socratic selections
        for ($i = 0; $i < 2; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();

            $this->service->recordSelection(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                recommendedFramework: 'socratic',
                chosenFramework: 'socratic'
            );
        }

        // Create 1 stoic selection
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $this->service->recordSelection(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'stoic',
            chosenFramework: 'stoic'
        );

        $socrateRate = $this->service->getAcceptanceRate('socratic');
        $stoicRate = $this->service->getAcceptanceRate('stoic');

        expect($socrateRate)->toBe(100.0);
        expect($stoicRate)->toBe(100.0);
    });

    test('preserves other framework data when updating outcome', function () {
        $promptRun1 = PromptRun::factory()->for($this->user)->create();
        $selection1 = $this->service->recordSelection(
            promptRun: $promptRun1,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'socratic',
            chosenFramework: 'socratic'
        );

        $promptRun2 = PromptRun::factory()->for($this->user)->create();
        $selection2 = $this->service->recordSelection(
            promptRun: $promptRun2,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            recommendedFramework: 'stoic',
            chosenFramework: 'stoic'
        );

        // Update first selection
        $this->service->updateWithOutcome($selection1, promptRating: 5);

        // Verify second selection unchanged
        $selection2Fresh = $selection2->fresh();
        expect($selection2Fresh->prompt_rating)->toBeNull();
    });
});
