<?php

namespace App\Services;

use App\Models\PromptQualityMetric;
use App\Models\PromptRun;
use Illuminate\Support\Facades\Log;

class PromptQualityService
{
    /**
     * Create or update prompt quality metrics for a prompt run
     *
     * Called when prompt generation completes or when outcomes are recorded
     */
    public function recordMetrics(
        PromptRun $promptRun,
        ?int $userRating = null,
        ?string $ratingExplanation = null,
        ?bool $shouldUpdateExplanation = false,
        ?bool $wasCopied = null,
        ?int $copyCount = null,
        ?bool $wasEdited = null,
        ?float $editPercentage = null,
        ?int $promptLength = null,
        ?int $questionsAnswered = null,
        ?int $questionsSkipped = null,
        ?int $timeToCompleteMs = null,
        ?string $taskCategory = null,
        ?string $frameworkUsed = null,
        ?string $personalityType = null,
        ?float $engagementScore = null,
        ?float $qualityScore = null,
    ): PromptQualityMetric {
        try {
            $existing = PromptQualityMetric::where('prompt_run_id', $promptRun->id)->first();

            if ($existing) {
                $updateData = [
                    'user_rating' => $userRating ?? $existing->user_rating,
                    'was_copied' => $wasCopied ?? $existing->was_copied,
                    'copy_count' => $copyCount ?? $existing->copy_count,
                    'was_edited' => $wasEdited ?? $existing->was_edited,
                    'edit_percentage' => $editPercentage ?? $existing->edit_percentage,
                    'prompt_length' => $promptLength ?? $existing->prompt_length,
                    'questions_answered' => $questionsAnswered ?? $existing->questions_answered,
                    'questions_skipped' => $questionsSkipped ?? $existing->questions_skipped,
                    'time_to_complete_ms' => $timeToCompleteMs ?? $existing->time_to_complete_ms,
                    'task_category' => $taskCategory ?? $existing->task_category,
                    'framework_used' => $frameworkUsed ?? $existing->framework_used,
                    'personality_type' => $personalityType ?? $existing->personality_type,
                    'engagement_score' => $engagementScore ?? $existing->engagement_score,
                    'quality_score' => $qualityScore ?? $existing->quality_score,
                ];

                // Only update explanation if explicitly provided (allows clearing with NULL)
                if ($shouldUpdateExplanation) {
                    $updateData['rating_explanation'] = $ratingExplanation;
                    $existing->update($updateData);
                } else {
                    $existing->update(array_filter($updateData, fn ($value) => $value !== null));
                }

                $metric = $existing->refresh();
            } else {
                $metric = PromptQualityMetric::create(array_filter([
                    'prompt_run_id' => $promptRun->id,
                    'user_rating' => $userRating,
                    'rating_explanation' => $ratingExplanation,
                    'was_copied' => $wasCopied,
                    'copy_count' => $copyCount,
                    'was_edited' => $wasEdited,
                    'edit_percentage' => $editPercentage,
                    'prompt_length' => $promptLength,
                    'questions_answered' => $questionsAnswered,
                    'questions_skipped' => $questionsSkipped,
                    'time_to_complete_ms' => $timeToCompleteMs,
                    'task_category' => $taskCategory,
                    'framework_used' => $frameworkUsed,
                    'personality_type' => $personalityType,
                    'engagement_score' => $engagementScore,
                    'quality_score' => $qualityScore,
                ], fn ($value) => $value !== null));
            }

            Log::info('Prompt quality metrics recorded', [
                'prompt_run_id' => $promptRun->id,
                'rating' => $userRating,
                'copied' => $wasCopied,
                'quality_score' => $qualityScore,
            ]);

            return $metric;
        } catch (\Exception $e) {
            Log::error('Failed to record prompt quality metrics', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate engagement score based on metrics
     *
     * Engagement score: 0-100 based on copy/edit/rating
     */
    public function calculateEngagementScore(PromptQualityMetric $metric): float
    {
        $score = 0;

        // Copy bonus: +30 points
        if ($metric->was_copied) {
            $score += 30;
        }

        // Edit bonus: +20 points (indicates engagement)
        if ($metric->was_edited) {
            $score += 20;
        }

        // Rating bonus: +50 points (scaled by rating)
        if ($metric->user_rating) {
            $score += ($metric->user_rating / 5) * 50;
        }

        return min(100, max(0, $score));
    }

    /**
     * Calculate quality score based on metrics
     *
     * Quality score: 0-100 based on length, edit %, time, framework effectiveness
     */
    public function calculateQualityScore(PromptQualityMetric $metric): float
    {
        $score = 0;

        // Prompt length: optimal is 500-2000 chars
        if ($metric->prompt_length) {
            if ($metric->prompt_length >= 500 && $metric->prompt_length <= 2000) {
                $score += 25;
            } elseif ($metric->prompt_length > 200 && $metric->prompt_length < 3000) {
                $score += 15;
            }
        }

        // Edit percentage: low edits = high quality
        if ($metric->edit_percentage !== null) {
            if ($metric->edit_percentage <= 10) {
                $score += 25;
            } elseif ($metric->edit_percentage <= 30) {
                $score += 15;
            }
        }

        // Questions answered: shows good input
        if ($metric->questions_answered && $metric->questions_answered > 0) {
            $score += 20;
        }

        // Time to complete: reasonable time = quality
        if ($metric->time_to_complete_ms) {
            if ($metric->time_to_complete_ms >= 30000 && $metric->time_to_complete_ms <= 600000) {
                $score += 30;
            }
        }

        return min(100, max(0, $score));
    }

    /**
     * Get average quality by framework
     */
    public function getFrameworkQuality(string $framework): ?float
    {
        return PromptQualityMetric::where('framework_used', $framework)
            ->whereNotNull('quality_score')
            ->avg('quality_score');
    }

    /**
     * Get average quality by personality type
     */
    public function getPersonalityTypeQuality(string $personalityType): ?float
    {
        return PromptQualityMetric::where('personality_type', $personalityType)
            ->whereNotNull('quality_score')
            ->avg('quality_score');
    }

    /**
     * Get average quality by task category
     */
    public function getTaskCategoryQuality(string $taskCategory): ?float
    {
        return PromptQualityMetric::where('task_category', $taskCategory)
            ->whereNotNull('quality_score')
            ->avg('quality_score');
    }

    /**
     * Get overall quality metrics summary
     */
    public function getOverallQuality(): array
    {
        $total = PromptQualityMetric::count();

        if ($total === 0) {
            return [
                'total_prompts' => 0,
                'average_quality_score' => 0,
                'average_engagement_score' => 0,
                'average_rating' => 0,
                'copy_rate' => 0,
                'edit_rate' => 0,
            ];
        }

        return [
            'total_prompts' => $total,
            'average_quality_score' => PromptQualityMetric::avg('quality_score') ?? 0,
            'average_engagement_score' => PromptQualityMetric::avg('engagement_score') ?? 0,
            'average_rating' => PromptQualityMetric::avg('user_rating') ?? 0,
            'copy_rate' => (PromptQualityMetric::where('was_copied', true)->count() / $total) * 100,
            'edit_rate' => (PromptQualityMetric::where('was_edited', true)->count() / $total) * 100,
        ];
    }

    /**
     * Get quality percentiles for comparison
     */
    public function getQualityPercentiles(): array
    {
        $metrics = PromptQualityMetric::orderBy('quality_score')->pluck('quality_score')->toArray();

        if (empty($metrics)) {
            return [];
        }

        $count = count($metrics);

        return [
            'p10' => $metrics[intval($count * 0.1)],
            'p25' => $metrics[intval($count * 0.25)],
            'p50' => $metrics[intval($count * 0.5)],
            'p75' => $metrics[intval($count * 0.75)],
            'p90' => $metrics[intval($count * 0.9)],
        ];
    }

    /**
     * Identify improvement opportunities
     */
    public function getImprovementOpportunities(): array
    {
        $opportunities = [];

        // Low engagement areas
        $lowEngagement = PromptQualityMetric::where('engagement_score', '<', 25)
            ->whereNotNull('framework_used')
            ->selectRaw('framework_used, count(*) as count')
            ->groupBy('framework_used')
            ->orderByDesc('count')
            ->get();

        if ($lowEngagement->isNotEmpty()) {
            $opportunities[] = [
                'type' => 'low_engagement_framework',
                'description' => 'Frameworks with low user engagement',
                'data' => $lowEngagement->toArray(),
            ];
        }

        // High edit rate
        $highEdit = PromptQualityMetric::where('edit_percentage', '>', 50)->count();
        if ($highEdit > ($total = PromptQualityMetric::count()) * 0.2) {
            $opportunities[] = [
                'type' => 'high_edit_rate',
                'description' => 'More than 20% of prompts have high edit rates',
                'percentage' => ($highEdit / $total) * 100,
            ];
        }

        return $opportunities;
    }
}
