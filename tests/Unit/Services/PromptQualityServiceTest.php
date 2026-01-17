<?php

use App\Models\PromptQualityMetric;
use App\Models\PromptRun;
use App\Models\User;
use App\Services\PromptQualityService;

beforeEach(function () {
    $this->service = app(PromptQualityService::class);
    $this->user = User::factory()->create();
});

describe('Prompt Quality Service - Metrics Recording', function () {
    test('creates new quality metrics record', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            userRating: 5,
            wasCopied: true,
            wasEdited: false
        );

        expect($metric)->toBeInstanceOf(PromptQualityMetric::class)
            ->and($metric->prompt_run_id)->toBe($promptRun->id)
            ->and($metric->user_rating)->toBe(5)
            ->and($metric->was_copied)->toBeTrue()
            ->and($metric->was_edited)->toBeFalse();
    });

    test('records metrics with all available fields', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            userRating: 4,
            wasCopied: true,
            copyCount: 2,
            wasEdited: true,
            editPercentage: 15.5,
            promptLength: 1200,
            questionsAnswered: 5,
            questionsSkipped: 1,
            timeToCompleteMs: 180000,
            taskCategory: 'research',
            frameworkUsed: 'socratic',
            personalityType: 'INTJ',
            engagementScore: 75.0,
            qualityScore: 82.5
        );

        expect($metric->user_rating)->toBe(4)
            ->and($metric->was_copied)->toBeTrue()
            ->and($metric->copy_count)->toBe(2)
            ->and($metric->was_edited)->toBeTrue()
            ->and($metric->edit_percentage)->toBe(15.5)
            ->and($metric->prompt_length)->toBe(1200)
            ->and($metric->questions_answered)->toBe(5)
            ->and($metric->questions_skipped)->toBe(1)
            ->and($metric->time_to_complete_ms)->toBe(180000)
            ->and($metric->task_category)->toBe('research')
            ->and($metric->framework_used)->toBe('socratic')
            ->and($metric->personality_type)->toBe('INTJ')
            ->and($metric->engagement_score)->toBe(75.0)
            ->and($metric->quality_score)->toBe(82.5);
    });

    test('updates existing metrics instead of creating new record', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        // Create initial metrics
        $this->service->recordMetrics(
            promptRun: $promptRun,
            userRating: 3,
            wasCopied: false
        );

        // Update metrics
        $this->service->recordMetrics(
            promptRun: $promptRun,
            userRating: 5,
            wasCopied: true
        );

        $count = PromptQualityMetric::where('prompt_run_id', $promptRun->id)->count();
        expect($count)->toBe(1);

        $metric = PromptQualityMetric::where('prompt_run_id', $promptRun->id)->first();
        expect($metric->user_rating)->toBe(5)
            ->and($metric->was_copied)->toBeTrue();
    });

    test('preserves existing data when updating partial fields', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        // Create full metrics
        $this->service->recordMetrics(
            promptRun: $promptRun,
            userRating: 4,
            wasCopied: true,
            promptLength: 1000,
            frameworkUsed: 'socratic'
        );

        // Update only rating
        $this->service->recordMetrics(
            promptRun: $promptRun,
            userRating: 5
        );

        $metric = PromptQualityMetric::where('prompt_run_id', $promptRun->id)->first();
        expect($metric->user_rating)->toBe(5)
            ->and($metric->was_copied)->toBeTrue()
            ->and($metric->prompt_length)->toBe(1000)
            ->and($metric->framework_used)->toBe('socratic');
    });

    test('handles sparse data (only some fields provided)', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();

        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            userRating: 4
        );

        expect($metric->user_rating)->toBe(4)
            ->and($metric->was_copied)->toBeNull()
            ->and($metric->prompt_length)->toBeNull()
            ->and($metric->engagement_score)->toBeNull();
    });
});

describe('Prompt Quality Service - Engagement Score', function () {
    test('calculates engagement score for copied prompt', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            wasCopied: true,
            wasEdited: false,
            userRating: null
        );

        $score = $this->service->calculateEngagementScore($metric);

        expect($score)->toBe(30.0);
    });

    test('calculates engagement score for edited prompt', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            wasCopied: false,
            wasEdited: true,
            userRating: null
        );

        $score = $this->service->calculateEngagementScore($metric);

        expect($score)->toBe(20.0);
    });

    test('calculates engagement score with rating (5 stars)', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            wasCopied: false,
            wasEdited: false,
            userRating: 5
        );

        $score = $this->service->calculateEngagementScore($metric);

        expect($score)->toBe(50.0);
    });

    test('calculates engagement score with rating (3 stars)', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            wasCopied: false,
            wasEdited: false,
            userRating: 3
        );

        $score = $this->service->calculateEngagementScore($metric);

        expect($score)->toBe(30.0);
    });

    test('combines all engagement factors', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            wasCopied: true,
            wasEdited: true,
            userRating: 5
        );

        $score = $this->service->calculateEngagementScore($metric);

        expect($score)->toBe(100.0);
    });

    test('engagement score is capped at 100', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            wasCopied: true,
            wasEdited: true,
            userRating: 5
        );

        $score = $this->service->calculateEngagementScore($metric);

        expect($score)->toBeLessThanOrEqual(100.0);
    });

    test('engagement score is at least 0', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            wasCopied: false,
            wasEdited: false,
            userRating: null
        );

        $score = $this->service->calculateEngagementScore($metric);

        expect($score)->toBeGreaterThanOrEqual(0.0);
    });
});

describe('Prompt Quality Service - Quality Score', function () {
    test('gives bonus for optimal prompt length (500-2000)', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            promptLength: 1200
        );

        $score = $this->service->calculateQualityScore($metric);

        expect($score)->toBeGreaterThanOrEqual(25.0);
    });

    test('gives smaller bonus for acceptable prompt length (200-3000)', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            promptLength: 350
        );

        $score = $this->service->calculateQualityScore($metric);

        expect($score)->toBeGreaterThanOrEqual(15.0);
    });

    test('gives bonus for low edit percentage (<=10%)', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            editPercentage: 5.0
        );

        $score = $this->service->calculateQualityScore($metric);

        expect($score)->toBeGreaterThanOrEqual(25.0);
    });

    test('gives smaller bonus for moderate edit percentage (<=30%)', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            editPercentage: 20.0
        );

        $score = $this->service->calculateQualityScore($metric);

        expect($score)->toBeGreaterThanOrEqual(15.0);
    });

    test('gives bonus for questions answered', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            questionsAnswered: 5
        );

        $score = $this->service->calculateQualityScore($metric);

        expect($score)->toBeGreaterThanOrEqual(20.0);
    });

    test('gives bonus for reasonable completion time (30-600 seconds)', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            timeToCompleteMs: 180000 // 3 minutes
        );

        $score = $this->service->calculateQualityScore($metric);

        expect($score)->toBeGreaterThanOrEqual(30.0);
    });

    test('combines all quality factors', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            promptLength: 1500,
            editPercentage: 5.0,
            questionsAnswered: 5,
            timeToCompleteMs: 300000
        );

        $score = $this->service->calculateQualityScore($metric);

        expect($score)->toBeGreaterThanOrEqual(100.0);
    });

    test('quality score is capped at 100', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $metric = $this->service->recordMetrics(
            promptRun: $promptRun,
            promptLength: 1500,
            editPercentage: 5.0,
            questionsAnswered: 10,
            timeToCompleteMs: 300000
        );

        $score = $this->service->calculateQualityScore($metric);

        expect($score)->toBeLessThanOrEqual(100.0);
    });

    test('quality score is at least 0', function () {
        $promptRun = PromptRun::factory()->for($this->user)->create();
        $metric = $this->service->recordMetrics($promptRun);

        $score = $this->service->calculateQualityScore($metric);

        expect($score)->toBeGreaterThanOrEqual(0.0);
    });
});

describe('Prompt Quality Service - Quality By Framework', function () {
    test('calculates average quality for framework', function () {
        // Create 3 metrics with different quality scores
        foreach ([75, 80, 85] as $score) {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $this->service->recordMetrics(
                promptRun: $promptRun,
                frameworkUsed: 'socratic',
                qualityScore: $score
            );
        }

        $average = $this->service->getFrameworkQuality('socratic');

        expect($average)->toBe(80.0);
    });

    test('ignores metrics without quality score', function () {
        // Create metric with quality score
        $promptRun1 = PromptRun::factory()->for($this->user)->create();
        $this->service->recordMetrics(
            promptRun: $promptRun1,
            frameworkUsed: 'socratic',
            qualityScore: 100.0
        );

        // Create metric without quality score
        $promptRun2 = PromptRun::factory()->for($this->user)->create();
        $this->service->recordMetrics(
            promptRun: $promptRun2,
            frameworkUsed: 'socratic'
        );

        $average = $this->service->getFrameworkQuality('socratic');

        expect($average)->toBe(100.0);
    });

    test('returns null for framework with no metrics', function () {
        $average = $this->service->getFrameworkQuality('nonexistent');

        expect($average)->toBeNull();
    });
});

describe('Prompt Quality Service - Quality By Personality Type', function () {
    test('calculates average quality for personality type', function () {
        foreach ([70, 75, 80] as $score) {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $this->service->recordMetrics(
                promptRun: $promptRun,
                personalityType: 'INTJ',
                qualityScore: $score
            );
        }

        $average = $this->service->getPersonalityTypeQuality('INTJ');

        expect($average)->toBe(75.0);
    });

    test('returns null for personality type with no metrics', function () {
        $average = $this->service->getPersonalityTypeQuality('NONEXISTENT');

        expect($average)->toBeNull();
    });
});

describe('Prompt Quality Service - Quality By Task Category', function () {
    test('calculates average quality for task category', function () {
        foreach ([60, 70, 80] as $score) {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $this->service->recordMetrics(
                promptRun: $promptRun,
                taskCategory: 'research',
                qualityScore: $score
            );
        }

        $average = $this->service->getTaskCategoryQuality('research');

        expect($average)->toBe(70.0);
    });

    test('returns null for task category with no metrics', function () {
        $average = $this->service->getTaskCategoryQuality('nonexistent');

        expect($average)->toBeNull();
    });
});

describe('Prompt Quality Service - Overall Quality', function () {
    test('returns empty summary for no metrics', function () {
        $summary = $this->service->getOverallQuality();

        expect($summary)
            ->toHaveKeys([
                'total_prompts', 'average_quality_score', 'average_engagement_score', 'average_rating', 'copy_rate',
                'edit_rate',
            ])
            ->and($summary['total_prompts'])->toBe(0)
            ->and($summary['average_quality_score'])->toBe(0)
            ->and($summary['copy_rate'])->toBe(0);
    });

    test('calculates overall quality summary', function () {
        // Create 4 metrics
        for ($i = 0; $i < 4; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $this->service->recordMetrics(
                promptRun: $promptRun,
                userRating: 4,
                wasCopied: $i < 2, // 50% copied
                wasEdited: $i < 1, // 25% edited
                qualityScore: 75.0,
                engagementScore: 60.0
            );
        }

        $summary = $this->service->getOverallQuality();

        expect($summary['total_prompts'])->toBe(4)
            ->and($summary['average_quality_score'])->toBe(75.0)
            ->and($summary['average_engagement_score'])->toBe(60.0)
            ->and($summary['average_rating'])->toBe(4.0)
            ->and($summary['copy_rate'])->toBe(50.0)
            ->and($summary['edit_rate'])->toBe(25.0);
    });
});

describe('Prompt Quality Service - Quality Percentiles', function () {
    test('returns empty array for no metrics', function () {
        $percentiles = $this->service->getQualityPercentiles();

        expect($percentiles)->toBeEmpty();
    });

    test('calculates quality percentiles', function () {
        // Create 10 metrics with scores 10-100
        for ($i = 1; $i <= 10; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $this->service->recordMetrics(
                promptRun: $promptRun,
                qualityScore: $i * 10
            );
        }

        $percentiles = $this->service->getQualityPercentiles();

        expect($percentiles)
            ->toHaveKeys(['p10', 'p25', 'p50', 'p75', 'p90'])
            ->and($percentiles['p50'])->toBe(50.0)
            ->and($percentiles['p90'])->toBe(90.0);
    });
});

describe('Prompt Quality Service - Improvement Opportunities', function () {
    test('identifies low engagement frameworks', function () {
        // Create 3 metrics with low engagement for 'socratic'
        for ($i = 0; $i < 3; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $this->service->recordMetrics(
                promptRun: $promptRun,
                frameworkUsed: 'socratic',
                engagementScore: 15.0
            );
        }

        $opportunities = $this->service->getImprovementOpportunities();

        $lowEngagementOpportunity = collect($opportunities)
            ->firstWhere('type', 'low_engagement_framework');

        expect($lowEngagementOpportunity)->not->toBeNull();
    });

    test('identifies high edit rate problems', function () {
        // Create 5 metrics, 2 with high edit rates (>20% of total)
        for ($i = 0; $i < 5; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $this->service->recordMetrics(
                promptRun: $promptRun,
                editPercentage: $i < 2 ? 60.0 : 5.0
            );
        }

        $opportunities = $this->service->getImprovementOpportunities();

        $highEditOpportunity = collect($opportunities)
            ->firstWhere('type', 'high_edit_rate');

        expect($highEditOpportunity)->not->toBeNull();
    });

    test('returns empty array when no issues found', function () {
        // Create 3 metrics with good engagement and low edit rates
        for ($i = 0; $i < 3; $i++) {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $this->service->recordMetrics(
                promptRun: $promptRun,
                frameworkUsed: 'socratic',
                engagementScore: 75.0,
                editPercentage: 5.0
            );
        }

        $opportunities = $this->service->getImprovementOpportunities();

        expect($opportunities)->toBeEmpty();
    });
});
