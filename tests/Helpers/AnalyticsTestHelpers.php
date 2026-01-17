<?php

namespace Tests\Helpers;

use App\Models\FrameworkAnalytic;
use App\Models\PromptQualityMetric;
use App\Models\PromptRun;
use App\Models\QuestionAnalytic;
use App\Models\User;
use App\Models\Visitor;

/**
 * Analytics Test Helpers
 *
 * Provides convenient test setup methods for analytics testing,
 * reducing boilerplate and making tests more readable.
 */
class AnalyticsTestHelpers
{
    /**
     * Create a prompt run with complete analytics setup
     *
     * Useful for integration tests that need a fully initialized prompt run
     * with framework selection and question presentations.
     *
     * @return array ['promptRun' => PromptRun, 'analytics' => [QuestionAnalytic, FrameworkAnalytic]]
     */
    public static function createPromptRunWithAnalytics(
        string $workflowStage = '1_completed',
        array $options = []
    ): array {
        $user = User::factory()->create();
        $visitor = Visitor::factory()->create();

        $promptRun = PromptRun::factory()
            ->for($user)
            ->create([
                'workflow_stage' => $workflowStage,
                'selected_framework' => $options['framework'] ?? [
                    'name' => 'Socratic',
                    'code' => 'socratic',
                    'components' => ['question', 'follow-up', 'reflection'],
                ],
                'framework_questions' => $options['questions'] ?? [
                    ['id' => 'q-1', 'question' => 'How would you approach this?', 'category' => 'framework'],
                    ['id' => 'q-2', 'question' => 'What are the key considerations?', 'category' => 'framework'],
                    ['id' => 'q-3', 'question' => 'What might you be missing?', 'category' => 'reflection'],
                ],
            ]);

        // Create framework analytics
        $frameworkAnalytic = FrameworkAnalytic::factory()
            ->for($promptRun)
            ->create([
                'visitor_id' => $visitor->id,
                'user_id' => $user->id,
                'recommended_framework' => $options['framework']['code'] ?? 'socratic',
                'chosen_framework' => $options['framework']['code'] ?? 'socratic',
            ]);

        // Create question analytics for each framework question
        $questionAnalytics = [];
        $questions = $options['questions'] ?? [
            ['id' => 'q-1', 'question' => 'Question 1', 'category' => 'framework'],
            ['id' => 'q-2', 'question' => 'Question 2', 'category' => 'framework'],
            ['id' => 'q-3', 'question' => 'Question 3', 'category' => 'reflection'],
        ];

        foreach ($questions as $index => $question) {
            $questionAnalytics[] = QuestionAnalytic::factory()
                ->for($promptRun)
                ->create([
                    'visitor_id' => $visitor->id,
                    'user_id' => $user->id,
                    'question_id' => $question['id'],
                    'question_category' => $question['category'] ?? 'framework',
                    'display_order' => $index + 1,
                ]);
        }

        return [
            'promptRun' => $promptRun,
            'user' => $user,
            'visitor' => $visitor,
            'frameworkAnalytic' => $frameworkAnalytic,
            'questionAnalytics' => $questionAnalytics,
        ];
    }

    /**
     * Create question analytics with specific ratings
     *
     * Useful for testing rating-related functionality.
     *
     * @param  array  $ratings  [['question_id' => 'q-1', 'rating' => 5, 'explanation' => '...'], ...]
     * @return array QuestionAnalytic instances
     */
    public static function createRatedQuestions(
        PromptRun $promptRun,
        array $ratings = []
    ): array {
        $analytics = [];

        foreach ($ratings as $ratingData) {
            $analytic = QuestionAnalytic::factory()
                ->for($promptRun)
                ->create([
                    'question_id' => $ratingData['question_id'],
                    'user_rating' => $ratingData['rating'] ?? null,
                    'rating_explanation' => $ratingData['explanation'] ?? null,
                ]);

            $analytics[] = $analytic;
        }

        return $analytics;
    }

    /**
     * Create multiple framework selections to test analytics calculations
     *
     * Useful for testing acceptance rates, average ratings, etc.
     *
     * @param  array  $selections  [['accepted' => true, 'rating' => 5], ...]
     * @return array FrameworkAnalytic instances
     */
    public static function createFrameworkSelections(
        string $framework,
        array $selections = []
    ): array {
        $analytics = [];

        foreach ($selections as $selectionData) {
            $user = User::factory()->create();
            $promptRun = PromptRun::factory()->for($user)->create();

            $chosenFramework = $selectionData['accepted'] ?? true ? $framework : 'alternative';

            $analytic = FrameworkAnalytic::factory()
                ->for($promptRun)
                ->create([
                    'recommended_framework' => $framework,
                    'chosen_framework' => $chosenFramework,
                    'accepted_recommendation' => $selectionData['accepted'] ?? true,
                    'prompt_rating' => $selectionData['rating'] ?? null,
                    'prompt_copied' => $selectionData['copied'] ?? null,
                ]);

            $analytics[] = $analytic;
        }

        return $analytics;
    }

    /**
     * Create prompt quality metrics with specific values for testing
     *
     * Useful for testing quality score calculations.
     */
    public static function createPromptQualityMetric(
        PromptRun $promptRun,
        array $metrics = []
    ): PromptQualityMetric {
        return PromptQualityMetric::factory()
            ->for($promptRun)
            ->create([
                'user_rating' => $metrics['rating'] ?? null,
                'was_copied' => $metrics['copied'] ?? null,
                'was_edited' => $metrics['edited'] ?? null,
                'edit_percentage' => $metrics['edit_percentage'] ?? null,
                'prompt_length' => $metrics['length'] ?? null,
                'questions_answered' => $metrics['questions_answered'] ?? null,
                'questions_skipped' => $metrics['questions_skipped'] ?? null,
                'time_to_complete_ms' => $metrics['time_ms'] ?? null,
                'framework_used' => $metrics['framework'] ?? 'socratic',
                'personality_type' => $metrics['personality'] ?? 'INTJ',
                'task_category' => $metrics['task_category'] ?? 'research',
            ]);
    }

    /**
     * Verify question rating was saved correctly
     *
     * Useful assertion helper for testing question rating endpoints.
     */
    public static function assertQuestionRating(
        QuestionAnalytic $analytic,
        int $expectedRating,
        ?string $expectedExplanation = null
    ): void {
        expect($analytic->user_rating)->toBe($expectedRating);

        if ($expectedExplanation !== null) {
            expect($analytic->rating_explanation)->toBe($expectedExplanation);
        }
    }

    /**
     * Verify framework selection was tracked correctly
     *
     * Useful assertion helper for testing framework analytics.
     */
    public static function assertFrameworkSelection(
        FrameworkAnalytic $analytic,
        bool $expectedAccepted,
        ?int $expectedRating = null
    ): void {
        expect($analytic->accepted_recommendation)->toBe($expectedAccepted);

        if ($expectedRating !== null) {
            expect($analytic->prompt_rating)->toBe($expectedRating);
        }
    }

    /**
     * Verify prompt quality metrics are reasonable
     *
     * Useful validation helper to ensure metrics are within expected ranges.
     */
    public static function assertValidQualityMetrics(PromptQualityMetric $metric): void
    {
        if ($metric->user_rating !== null) {
            expect($metric->user_rating)->toBeGreaterThanOrEqual(1)
                ->and($metric->user_rating)->toBeLessThanOrEqual(5);
        }

        if ($metric->edit_percentage !== null) {
            expect($metric->edit_percentage)->toBeGreaterThanOrEqual(0)
                ->and($metric->edit_percentage)->toBeLessThanOrEqual(100);
        }

        if ($metric->questions_answered !== null && $metric->questions_skipped !== null) {
            expect($metric->questions_answered + $metric->questions_skipped)
                ->toBeGreaterThan(0);
        }
    }

    /**
     * Get analytics summary for a prompt run
     *
     * Useful for verifying complete analytics state after integration tests.
     *
     * @return array Summary of all analytics for this prompt run
     */
    public static function getPromptRunAnalyticsSummary(PromptRun $promptRun): array
    {
        $questionAnalytics = QuestionAnalytic::where('prompt_run_id', $promptRun->id)
            ->get();

        $frameworkAnalytics = FrameworkAnalytic::where('prompt_run_id', $promptRun->id)
            ->get();

        $qualityMetrics = PromptQualityMetric::where('prompt_run_id', $promptRun->id)
            ->first();

        return [
            'prompt_run_id' => $promptRun->id,
            'question_count' => $questionAnalytics->count(),
            'rated_questions' => $questionAnalytics->where('user_rating', '!=', null)->count(),
            'framework_selections' => $frameworkAnalytics->count(),
            'quality_metrics_recorded' => $qualityMetrics !== null,
            'average_question_rating' => $questionAnalytics
                ->whereNotNull('user_rating')
                ->avg('user_rating'),
            'framework_accepted' => $frameworkAnalytics->first()?->accepted_recommendation,
        ];
    }

    /**
     * Clean up analytics data for a prompt run
     *
     * Useful for explicit cleanup in tests that need to reset state.
     */
    public static function cleanupPromptRunAnalytics(PromptRun $promptRun): void
    {
        QuestionAnalytic::where('prompt_run_id', $promptRun->id)->delete();
        FrameworkAnalytic::where('prompt_run_id', $promptRun->id)->delete();
        PromptQualityMetric::where('prompt_run_id', $promptRun->id)->delete();
    }

    /**
     * Create test data for analytics reporting
     *
     * Useful for testing analytics dashboards and admin reports.
     *
     * @param  int  $ratingPercentage  (0-100)
     * @return array Structured test data
     */
    public static function createAnalyticsDataset(
        int $promptRunCount = 10,
        int $ratingPercentage = 70
    ): array {
        $data = [
            'prompt_runs' => [],
            'total_questions' => 0,
            'total_ratings' => 0,
        ];

        for ($i = 0; $i < $promptRunCount; $i++) {
            $setup = self::createPromptRunWithAnalytics();
            $promptRun = $setup['promptRun'];
            $questions = $setup['questionAnalytics'];

            // Rate some questions based on percentage
            $ratingCount = intval(count($questions) * ($ratingPercentage / 100));

            foreach (array_slice($questions, 0, $ratingCount) as $question) {
                $question->update([
                    'user_rating' => rand(1, 5),
                    'rating_explanation' => 'Test explanation',
                ]);

                $data['total_ratings']++;
            }

            $data['prompt_runs'][] = $promptRun;
            $data['total_questions'] += count($questions);
        }

        return $data;
    }
}
