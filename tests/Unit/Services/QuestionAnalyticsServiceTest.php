<?php

use App\Models\PromptRun;
use App\Models\QuestionAnalytic;
use App\Models\User;
use App\Models\Visitor;
use App\Services\QuestionAnalyticsService;

beforeEach(function () {
    $this->service = app(QuestionAnalyticsService::class);
    $this->user = User::factory()->create();
    $this->visitor = Visitor::factory()->create();
});

describe('Question Analytics Service - Presentation Recording', function () {
    test('records question presentation with all required fields', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        $analytic = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-1',
            questionCategory: 'framework',
            displayOrder: 1,
            wasRequired: true
        );

        expect($analytic)->toBeInstanceOf(QuestionAnalytic::class)
            ->and($analytic->prompt_run_id)->toBe($promptRun->id)
            ->and($analytic->question_id)->toBe('q-1')
            ->and($analytic->question_category)->toBe('framework')
            ->and($analytic->display_order)->toBe(1)
            ->and($analytic->was_required)->toBeTrue()
            ->and($analytic->response_status)->toBe('not_shown')
            ->and($analytic->presented_at)->not->toBeNull();
    });

    test('records question presentation with personality variant', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        $analytic = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-2',
            questionCategory: 'personality',
            personalityVariant: 'introvert',
            displayOrder: 2
        );

        expect($analytic->personality_variant)->toBe('introvert');
    });

    test('records optional question presentation', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        $analytic = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-3',
            questionCategory: 'framework',
            wasRequired: false
        );

        expect($analytic->was_required)->toBeFalse();
    });

    test('records multiple questions from same prompt run', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        $analytic1 = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-1',
            questionCategory: 'framework',
            displayOrder: 1
        );

        $analytic2 = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-2',
            questionCategory: 'framework',
            displayOrder: 2
        );

        expect(QuestionAnalytic::where('prompt_run_id', $promptRun->id)->count())->toBe(2);
    });
});

describe('Question Analytics Service - Response Recording', function () {
    test('records question response with metrics', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $analytic = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-1',
            questionCategory: 'framework'
        );

        $updated = $this->service->recordResponse(
            $analytic,
            responseLength: 150,
            timeToAnswerMs: 5000
        );

        expect($updated->response_status)->toBe('answered')
            ->and($updated->response_length)->toBe(150)
            ->and($updated->time_to_answer_ms)->toBe(5000);
    });

    test('records response with only response length', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $analytic = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-1',
            questionCategory: 'framework'
        );

        $updated = $this->service->recordResponse($analytic, responseLength: 200);

        expect($updated->response_length)->toBe(200)
            ->and($updated->time_to_answer_ms)->toBeNull();
    });

    test('records response with time to answer', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $analytic = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-1',
            questionCategory: 'framework'
        );

        $updated = $this->service->recordResponse($analytic, timeToAnswerMs: 3500);

        expect($updated->time_to_answer_ms)->toBe(3500);
    });
});

describe('Question Analytics Service - Skip Recording', function () {
    test('records question skip', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $analytic = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-1',
            questionCategory: 'framework'
        );

        $updated = $this->service->recordSkip($analytic, timeBeforeSkipMs: 2000);

        expect($updated->response_status)->toBe('skipped')
            ->and($updated->time_to_answer_ms)->toBe(2000);
    });

    test('records skip without time metric', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $analytic = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-1',
            questionCategory: 'framework'
        );

        $updated = $this->service->recordSkip($analytic);

        expect($updated->response_status)->toBe('skipped');
    });
});

describe('Question Analytics Service - Outcome Tracking', function () {
    test('updates analytic with prompt rating', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $analytic = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-1',
            questionCategory: 'framework'
        );

        $updated = $this->service->updateWithOutcome(
            $analytic,
            promptRating: 5
        );

        expect($updated->prompt_rating)->toBe(5);
    });

    test('updates analytic with prompt copied flag', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $analytic = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-1',
            questionCategory: 'framework'
        );

        $updated = $this->service->updateWithOutcome(
            $analytic,
            promptCopied: true
        );

        expect($updated->prompt_copied)->toBeTrue();
    });

    test('updates analytic with both outcome metrics', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $analytic = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-1',
            questionCategory: 'framework'
        );

        $updated = $this->service->updateWithOutcome(
            $analytic,
            promptRating: 4,
            promptCopied: true
        );

        expect($updated->prompt_rating)->toBe(4)
            ->and($updated->prompt_copied)->toBeTrue();
    });

    test('preserves existing outcome data when updating one field', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $analytic = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-1',
            questionCategory: 'framework'
        );

        // Set both values
        $this->service->updateWithOutcome($analytic, promptRating: 5, promptCopied: true);

        // Update only rating
        $updated = $this->service->updateWithOutcome(
            $analytic->fresh(),
            promptRating: 3
        );

        expect($updated->prompt_rating)->toBe(3)
            ->and($updated->prompt_copied)->toBeTrue();
    });
});

describe('Question Analytics Service - Rate Calculations', function () {
    test('calculates answer rate correctly', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        // Create 10 presentations of same question
        for ($i = 0; $i < 10; $i++) {
            $analytic = $this->service->recordPresentation(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                questionId: 'q-1',
                questionCategory: 'framework'
            );

            if ($i < 7) {
                // 7 answered
                $this->service->recordResponse($analytic, responseLength: 100);
            } else {
                // 3 skipped
                $this->service->recordSkip($analytic);
            }
        }

        $rate = $this->service->getAnswerRate('q-1');

        expect($rate)->toBe(70.0);
    });

    test('returns 0 answer rate for no presentations', function () {
        $rate = $this->service->getAnswerRate('q-nonexistent');

        expect($rate)->toBe(0.0);
    });

    test('calculates skip rate correctly', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        // Create 10 presentations
        for ($i = 0; $i < 10; $i++) {
            $analytic = $this->service->recordPresentation(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                questionId: 'q-1',
                questionCategory: 'framework'
            );

            if ($i < 6) {
                $this->service->recordResponse($analytic, responseLength: 100);
            } else {
                $this->service->recordSkip($analytic);
            }
        }

        $rate = $this->service->getSkipRate('q-1');

        expect($rate)->toBe(40.0);
    });

    test('answer and skip rates add up to 100', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        for ($i = 0; $i < 8; $i++) {
            $analytic = $this->service->recordPresentation(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                questionId: 'q-1',
                questionCategory: 'framework'
            );

            if ($i < 5) {
                $this->service->recordResponse($analytic, responseLength: 100);
            } else {
                $this->service->recordSkip($analytic);
            }
        }

        $answerRate = $this->service->getAnswerRate('q-1');
        $skipRate = $this->service->getSkipRate('q-1');

        expect($answerRate + $skipRate)->toBe(100.0);
    });
});

describe('Question Analytics Service - Average Calculations', function () {
    test('calculates average time to answer', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        // Create 3 answered questions with different times
        for ($time = 1000; $time <= 3000; $time += 1000) {
            $analytic = $this->service->recordPresentation(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                questionId: 'q-1',
                questionCategory: 'framework'
            );

            $this->service->recordResponse($analytic, timeToAnswerMs: $time);
        }

        $average = $this->service->getAverageTimeToAnswer('q-1');

        expect($average)->toBe(2000.0);
    });

    test('returns null average for no answered questions', function () {
        $average = $this->service->getAverageTimeToAnswer('q-nonexistent');

        expect($average)->toBeNull();
    });

    test('calculates average response length', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        // Create responses with different lengths
        for ($length = 100; $length <= 300; $length += 100) {
            $analytic = $this->service->recordPresentation(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                questionId: 'q-1',
                questionCategory: 'framework'
            );

            $this->service->recordResponse($analytic, responseLength: $length);
        }

        $average = $this->service->getAverageResponseLength('q-1');

        expect($average)->toBe(200.0);
    });

    test('ignores skipped questions in averages', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        // Create 2 answered and 1 skipped
        $analytic1 = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-1',
            questionCategory: 'framework'
        );
        $this->service->recordResponse($analytic1, responseLength: 100, timeToAnswerMs: 1000);

        $analytic2 = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-1',
            questionCategory: 'framework'
        );
        $this->service->recordResponse($analytic2, responseLength: 300, timeToAnswerMs: 3000);

        $analytic3 = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-1',
            questionCategory: 'framework'
        );
        $this->service->recordSkip($analytic3);

        $avgLength = $this->service->getAverageResponseLength('q-1');
        $avgTime = $this->service->getAverageTimeToAnswer('q-1');

        expect($avgLength)->toBe(200.0);
        expect($avgTime)->toBe(2000.0);
    });
});

describe('Question Analytics Service - Rating Correlation', function () {
    test('calculates answer rating correlation correctly', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        // Answered questions rated 5
        for ($i = 0; $i < 2; $i++) {
            $analytic = $this->service->recordPresentation(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                questionId: 'q-1',
                questionCategory: 'framework'
            );
            $this->service->recordResponse($analytic, responseLength: 100);
            $this->service->updateWithOutcome($analytic, promptRating: 5);
        }

        // Skipped questions rated 2
        for ($i = 0; $i < 2; $i++) {
            $analytic = $this->service->recordPresentation(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                questionId: 'q-1',
                questionCategory: 'framework'
            );
            $this->service->recordSkip($analytic);
            $this->service->updateWithOutcome($analytic, promptRating: 2);
        }

        $correlation = $this->service->getAnswerRatingCorrelation('q-1');

        expect($correlation)->toBe(3.0);
    });

    test('returns null correlation when missing data', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        $analytic = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-1',
            questionCategory: 'framework'
        );

        $this->service->recordResponse($analytic, responseLength: 100);

        $correlation = $this->service->getAnswerRatingCorrelation('q-1');

        expect($correlation)->toBeNull();
    });
});

describe('Question Analytics Service - Performance Analysis', function () {
    test('gets question performance summary', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        for ($i = 0; $i < 5; $i++) {
            $analytic = $this->service->recordPresentation(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                questionId: 'q-1',
                questionCategory: 'framework',
                displayOrder: 1
            );

            if ($i < 4) {
                $this->service->recordResponse($analytic, responseLength: 100, timeToAnswerMs: 2000);
            } else {
                $this->service->recordSkip($analytic);
            }
        }

        $performance = $this->service->getQuestionPerformance('q-1');

        expect($performance)
            ->toHaveKeys(['question_id', 'times_shown', 'answer_rate', 'skip_rate', 'average_time_to_answer_ms', 'average_response_length', 'answer_rating_correlation'])
            ->and($performance['question_id'])->toBe('q-1')
            ->and($performance['times_shown'])->toBe(5)
            ->and($performance['answer_rate'])->toBe(80.0)
            ->and($performance['skip_rate'])->toBe(20.0);
    });

    test('gets least effective questions', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        // Create q-1 with high skip rate (25%)
        for ($i = 0; $i < 4; $i++) {
            $analytic = $this->service->recordPresentation(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                questionId: 'q-1',
                questionCategory: 'framework'
            );

            if ($i < 3) {
                $this->service->recordResponse($analytic, responseLength: 100);
            } else {
                $this->service->recordSkip($analytic);
            }
        }

        // Create q-2 with low skip rate (0%)
        for ($i = 0; $i < 3; $i++) {
            $analytic = $this->service->recordPresentation(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                questionId: 'q-2',
                questionCategory: 'framework'
            );

            $this->service->recordResponse($analytic, responseLength: 100);
        }

        $leastEffective = $this->service->getLeastEffectiveQuestions(1);

        expect($leastEffective)->toHaveCount(1);
        expect($leastEffective[0]['question_id'])->toBe('q-1');
        expect($leastEffective[0]['skip_rate'])->toBe(25.0);
    });

    test('gets most effective questions', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        // Create q-1 with high answer rate and positive correlation
        for ($i = 0; $i < 4; $i++) {
            $analytic = $this->service->recordPresentation(
                promptRun: $promptRun,
                visitorId: $this->visitor->id,
                userId: $this->user->id,
                questionId: 'q-1',
                questionCategory: 'framework'
            );

            $this->service->recordResponse($analytic, responseLength: 100);
            $this->service->updateWithOutcome($analytic, promptRating: 5);
        }

        // Create q-2 with low answer rate
        $analytic = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-2',
            questionCategory: 'framework'
        );
        $this->service->recordSkip($analytic);

        $mostEffective = $this->service->getMostEffectiveQuestions(1);

        expect($mostEffective)->toHaveCount(1);
        expect($mostEffective[0]['question_id'])->toBe('q-1');
    });
});

describe('Question Analytics Service - Question Rating', function () {
    test('updates question with user rating', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create([
            'framework_questions' => [
                ['id' => 'q-1', 'category' => 'framework', 'required' => true],
            ],
        ]);

        $analytic = $this->service->recordPresentation(
            promptRun: $promptRun,
            visitorId: $this->visitor->id,
            userId: $this->user->id,
            questionId: 'q-1',
            questionCategory: 'framework'
        );

        $updated = $this->service->updateWithRating(
            promptRun: $promptRun,
            questionId: 'q-1',
            rating: 5,
            explanation: 'Great question!'
        );

        expect($updated->user_rating)->toBe(5)
            ->and($updated->rating_explanation)->toBe('Great question!');
    });

    test('creates analytic if rating given without presentation', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create([
            'framework_questions' => [
                ['id' => 'q-1', 'category' => 'framework', 'required' => true],
            ],
        ]);

        $updated = $this->service->updateWithRating(
            promptRun: $promptRun,
            questionId: 'q-1',
            rating: 4,
            explanation: 'Good question'
        );

        expect($updated->user_rating)->toBe(4);
        expect(QuestionAnalytic::where('prompt_run_id', $promptRun->id)->count())->toBe(1);
    });

    test('handles numeric question ID mapping', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create([
            'framework_questions' => [
                ['id' => 'custom-q-1', 'category' => 'framework', 'required' => true],
            ],
        ]);

        $updated = $this->service->updateWithRating(
            promptRun: $promptRun,
            questionId: 'Q0',
            rating: 3
        );

        expect($updated->question_id)->toBe('Q0');
    });

    test('updates existing rating instead of creating new analytic', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create([
            'framework_questions' => [
                ['id' => 'q-1', 'category' => 'framework', 'required' => true],
            ],
        ]);

        // Create initial rating
        $this->service->updateWithRating(
            promptRun: $promptRun,
            questionId: 'q-1',
            rating: 2,
            explanation: 'Initial'
        );

        // Update rating
        $this->service->updateWithRating(
            promptRun: $promptRun,
            questionId: 'q-1',
            rating: 5,
            explanation: 'Updated'
        );

        $count = QuestionAnalytic::where('prompt_run_id', $promptRun->id)->count();
        expect($count)->toBe(1);

        $analytic = QuestionAnalytic::where('prompt_run_id', $promptRun->id)->first();
        expect($analytic->user_rating)->toBe(5)
            ->and($analytic->rating_explanation)->toBe('Updated');
    });
});
