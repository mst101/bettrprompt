<?php

namespace App\Http\Controllers\Admin;

use App\Models\FrameworkDailyStat;
use App\Models\QuestionDailyStat;
use App\Models\WorkflowDailyStat;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainAnalyticsController
{
    /**
     * Get framework analytics for a date
     */
    public function getFrameworkAnalytics(Request $request): JsonResponse
    {
        $date = $request->query('date', now()->toDateString());
        $dateObj = Carbon::parse($date);

        $stats = FrameworkDailyStat::where('date', $dateObj->toDateString())->get();

        $frameworks = $stats->map(function ($stat) {
            return [
                'framework' => $stat->framework,
                'timesRecommended' => $stat->times_recommended,
                'timesChosen' => $stat->times_chosen,
                'acceptanceRate' => $stat->acceptance_rate * 100,
                'avgRating' => $stat->avg_rating,
                'copyRate' => $stat->copy_rate * 100,
            ];
        })->sortByDesc('acceptanceRate')->values();

        $totalRecommendations = $stats->sum('times_recommended');
        $totalAccepted = $stats->sum('times_accepted');
        $avgAcceptance = $totalRecommendations > 0 ? ($totalAccepted / $totalRecommendations) * 100 : 0;
        $avgRating = $stats->whereNotNull('avg_rating')->avg('avg_rating') ?? 0;
        $avgCopyRate = $stats->avg('copy_rate') * 100;

        return response()->json([
            'frameworks' => $frameworks,
            'stats' => [
                'totalRecommendations' => $totalRecommendations,
                'acceptanceRate' => $avgAcceptance,
                'avgRating' => $avgRating,
                'copyRate' => $avgCopyRate,
            ],
        ]);
    }

    /**
     * Get question analytics for a date
     */
    public function getQuestionAnalytics(Request $request): JsonResponse
    {
        $date = $request->query('date', now()->toDateString());
        $dateObj = Carbon::parse($date);

        $stats = QuestionDailyStat::where('date', $dateObj->toDateString())->get();

        $questions = $stats->map(function ($stat) {
            // Determine effectiveness
            $isEffective = ($stat->answer_rate ?? 0) >= 0.75
                && ($stat->avg_prompt_rating_when_answered ?? 0) >= 3
                && ($stat->skip_rate ?? 0) <= 0.25;

            return [
                'questionId' => $stat->question_id,
                'timesShown' => $stat->times_shown,
                'answerRate' => ($stat->answer_rate ?? 0) * 100,
                'skipRate' => ($stat->skip_rate ?? 0) * 100,
                'avgTimeMs' => $stat->avg_time_to_answer_ms,
                'avgResponseLength' => $stat->avg_response_length,
                'ratingWhenAnswered' => $stat->avg_prompt_rating_when_answered,
                'ratingWhenSkipped' => $stat->avg_prompt_rating_when_skipped,
                'isEffective' => $isEffective,
            ];
        })->sortByDesc('timesShown')->values();

        $totalShown = $stats->sum('times_shown');
        $totalAnswered = $stats->sum('times_answered');
        $totalSkipped = $stats->sum('times_skipped');
        $avgAnswerRate = $totalShown > 0 ? ($totalAnswered / $totalShown) * 100 : 0;
        $avgSkipRate = $totalShown > 0 ? ($totalSkipped / $totalShown) * 100 : 0;
        $avgTime = $stats->whereNotNull('avg_time_to_answer_ms')->avg('avg_time_to_answer_ms') ?? 0;

        return response()->json([
            'questions' => $questions,
            'stats' => [
                'totalShown' => $totalShown,
                'answerRate' => $avgAnswerRate,
                'skipRate' => $avgSkipRate,
                'avgTimeMs' => $avgTime,
            ],
        ]);
    }

    /**
     * Get workflow analytics for a date
     */
    public function getWorkflowAnalytics(Request $request): JsonResponse
    {
        $date = $request->query('date', now()->toDateString());
        $dateObj = Carbon::parse($date);

        $stats = WorkflowDailyStat::where('date', $dateObj->toDateString())->get();

        $stages = $stats->map(function ($stat) {
            return [
                'stage' => $stat->workflow_stage,
                'totalExecutions' => $stat->total_executions,
                'successful' => $stat->successful_executions,
                'failed' => $stat->failed_executions,
                'successRate' => ($stat->success_rate ?? 0) * 100,
                'avgDurationMs' => $stat->avg_duration_ms,
                'avgCostUsd' => $stat->avg_cost_per_execution,
                'totalCostUsd' => $stat->total_cost_usd,
            ];
        })->sortBy('stage')->values();

        $totalCost = $stats->sum('total_cost_usd');
        $totalInputTokens = $stats->sum('total_input_tokens');
        $totalOutputTokens = $stats->sum('total_output_tokens');
        $totalExecutions = $stats->sum('total_executions');

        // Get top errors across all stages
        $topErrors = [];
        foreach ($stats as $stat) {
            if ($stat->top_errors) {
                foreach ($stat->top_errors as $error) {
                    $topErrors[] = [
                        'errorCode' => $error['error_code'],
                        'count' => $error['count'],
                        'percentage' => $error['percentage'] ?? 0,
                        'message' => $this->getErrorMessage($error['error_code']),
                    ];
                }
            }
        }

        // Sort and limit to top 5
        usort($topErrors, fn ($a, $b) => $b['count'] <=> $a['count']);
        $topErrors = array_slice($topErrors, 0, 5);

        return response()->json([
            'stages' => $stages,
            'topErrors' => $topErrors,
            'totalCost' => $totalCost,
            'totalInputTokens' => $totalInputTokens,
            'totalOutputTokens' => $totalOutputTokens,
            'costPerExecution' => $totalExecutions > 0 ? ($totalCost / $totalExecutions) : 0,
        ]);
    }

    /**
     * Get human-readable error message
     */
    private function getErrorMessage(string $errorCode): string
    {
        return match ($errorCode) {
            'TIMEOUT' => 'Workflow execution timed out',
            'RATE_LIMIT' => 'API rate limit exceeded',
            'INVALID_INPUT' => 'Invalid input provided to workflow',
            'MODEL_ERROR' => 'Language model error',
            'NETWORK_ERROR' => 'Network connectivity error',
            default => ucfirst(strtolower(str_replace('_', ' ', $errorCode))),
        };
    }
}
