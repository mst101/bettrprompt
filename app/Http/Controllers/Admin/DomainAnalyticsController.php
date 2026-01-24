<?php

namespace App\Http\Controllers\Admin;

use App\Models\FrameworkDailyStat;
use App\Models\Funnel;
use App\Models\FunnelDailyStats;
use App\Models\FunnelProgress;
use App\Models\PromptRun;
use App\Models\QuestionDailyStat;
use App\Models\WorkflowDailyStat;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainAnalyticsController
{
    /**
     * Get framework analytics for a date range
     */
    public function getFrameworkAnalytics(Request $request): JsonResponse
    {
        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))->toDateString()
            : now()->toDateString();
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))->toDateString()
            : now()->subDays(29)->toDateString();

        $stats = FrameworkDailyStat::whereBetween('date', [$startDate, $endDate])->get();

        $frameworks = $stats->map(function ($stat) {
            return [
                'framework' => $stat->framework,
                'timesRecommended' => (int) $stat->times_recommended,
                'timesChosen' => (int) $stat->times_chosen,
                'acceptanceRate' => (float) ($stat->acceptance_rate * 100),
                'avgRating' => (float) $stat->avg_rating,
                'copyRate' => (float) ($stat->copy_rate * 100),
            ];
        })->sortByDesc('acceptanceRate')->values();

        $totalRecommendations = (int) $stats->sum('times_recommended');
        $totalAccepted = (int) $stats->sum('times_accepted');
        $avgAcceptance = $totalRecommendations > 0 ? (float) (($totalAccepted / $totalRecommendations) * 100) : 0.0;
        $avgRating = (float) ($stats->whereNotNull('avg_rating')->avg('avg_rating') ?? 0);
        $avgCopyRate = (float) ($stats->avg('copy_rate') * 100);

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
     * Get question analytics for a date range
     */
    public function getQuestionAnalytics(Request $request): JsonResponse
    {
        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))->toDateString()
            : now()->toDateString();
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))->toDateString()
            : now()->subDays(29)->toDateString();

        $stats = QuestionDailyStat::whereBetween('date', [$startDate, $endDate])->get();

        $questions = $stats->map(function ($stat) {
            // Determine effectiveness
            $isEffective = ($stat->answer_rate ?? 0) >= 0.75
                && ($stat->avg_prompt_rating_when_answered ?? 0) >= 3
                && ($stat->skip_rate ?? 0) <= 0.25;

            return [
                'questionId' => $stat->question_id,
                'timesShown' => (int) $stat->times_shown,
                'answerRate' => (float) (($stat->answer_rate ?? 0) * 100),
                'skipRate' => (float) (($stat->skip_rate ?? 0) * 100),
                'avgTimeMs' => (int) $stat->avg_time_to_answer_ms,
                'avgResponseLength' => (int) $stat->avg_response_length,
                'ratingWhenAnswered' => (float) $stat->avg_prompt_rating_when_answered,
                'ratingWhenSkipped' => (float) $stat->avg_prompt_rating_when_skipped,
                'isEffective' => $isEffective,
            ];
        })->sortByDesc('timesShown')->values();

        $totalShown = (int) $stats->sum('times_shown');
        $totalAnswered = (int) $stats->sum('times_answered');
        $totalSkipped = (int) $stats->sum('times_skipped');
        $avgAnswerRate = $totalShown > 0 ? (float) (($totalAnswered / $totalShown) * 100) : 0.0;
        $avgSkipRate = $totalShown > 0 ? (float) (($totalSkipped / $totalShown) * 100) : 0.0;
        $avgTime = (float) ($stats->whereNotNull('avg_time_to_answer_ms')->avg('avg_time_to_answer_ms') ?? 0);

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
     * Get workflow analytics for a date range
     */
    public function getWorkflowAnalytics(Request $request): JsonResponse
    {
        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))->toDateString()
            : now()->toDateString();
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))->toDateString()
            : now()->subDays(29)->toDateString();

        $stats = WorkflowDailyStat::whereBetween('date', [$startDate, $endDate])->get();

        $stages = $stats->map(function ($stat) {
            return [
                'stage' => (int) $stat->workflow_stage,
                'totalExecutions' => (int) $stat->total_executions,
                'successful' => (int) $stat->successful_executions,
                'failed' => (int) $stat->failed_executions,
                'successRate' => (float) (($stat->success_rate ?? 0) * 100),
                'avgDurationMs' => (int) $stat->avg_duration_ms,
                'avgCostUsd' => (float) $stat->avg_cost_per_execution,
                'totalCostUsd' => (float) $stat->total_cost_usd,
            ];
        })->sortBy('stage')->values();

        $totalCost = (float) $stats->sum('total_cost_usd');
        $totalInputTokens = (int) $stats->sum('total_input_tokens');
        $totalOutputTokens = (int) $stats->sum('total_output_tokens');
        $totalExecutions = (int) $stats->sum('total_executions');

        // Get top errors across all stages
        $topErrors = [];
        foreach ($stats as $stat) {
            if ($stat->top_errors) {
                foreach ($stat->top_errors as $error) {
                    $topErrors[] = [
                        'errorCode' => $error['error_code'],
                        'count' => (int) $error['count'],
                        'percentage' => (float) ($error['percentage'] ?? 0),
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
            'costPerExecution' => (float) ($totalExecutions > 0 ? ($totalCost / $totalExecutions) : 0),
        ]);
    }

    /**
     * Get prompt run analytics for a date range
     */
    public function getPromptRunsAnalytics(Request $request): JsonResponse
    {
        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->subDays(29)->startOfDay();

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Validate sort parameters
        $validSortColumns = ['taskDescription', 'personalityType', 'framework', 'createdBy', 'status', 'durationMs', 'createdAt'];
        if (! in_array($sortBy, $validSortColumns)) {
            $sortBy = 'createdAt';
        }
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query = PromptRun::whereBetween('created_at', [$startDate, $endDate])
            ->with(['user:id,name', 'visitor:id,country_code']);

        // Apply sorting (map frontend names to database column names)
        $sortColumnMap = [
            'taskDescription' => 'task_description',
            'personalityType' => 'personality_type',
            'framework' => 'framework_used',
            'createdBy' => 'user_id',
            'status' => 'workflow_stage',
            'durationMs' => 'completed_at',
            'createdAt' => 'created_at',
        ];

        $dbColumn = $sortColumnMap[$sortBy] ?? 'created_at';
        $runs = $query->orderBy($dbColumn, $sortDirection)->get();

        // Calculate summary stats
        $totalRuns = $runs->count();
        $completedRuns = $runs->filter(fn ($run) => $run->isCompleted)->count();
        $failedRuns = $runs->filter(fn ($run) => $run->isFailed)->count();
        $processingRuns = $runs->filter(fn ($run) => $run->isProcessing)->count();

        $successRate = $totalRuns > 0 ? ($completedRuns / $totalRuns) * 100 : 0;

        // Calculate average duration (from created_at to completed_at for completed runs)
        $completedRunsCollection = $runs->filter(fn ($run) => $run->isCompleted);
        $avgDurationMs = $completedRunsCollection->count() > 0
            ? $completedRunsCollection
                ->map(function ($run) {
                    if ($run->completed_at && $run->created_at) {
                        return abs($run->created_at->diffInMilliseconds($run->completed_at));
                    }

                    return 0;
                })
                ->filter(fn ($ms) => $ms > 0)
                ->avg()
            : 0;

        // Format runs for table display
        $runsData = $runs->map(function ($run) {
            $framework = 'N/A';
            if (is_array($run->framework_used) && isset($run->framework_used['name'])) {
                $framework = $run->framework_used['name'];
            } elseif ($run->selected_framework && is_array($run->selected_framework) && isset($run->selected_framework['name'])) {
                $framework = $run->selected_framework['name'];
            }

            $durationMs = null;
            if ($run->completed_at && $run->created_at) {
                $durationMs = abs($run->created_at->diffInMilliseconds($run->completed_at));
            }

            return [
                'id' => $run->id,
                'personalityType' => $run->personality_type ?? 'N/A',
                'framework' => $framework,
                'createdBy' => $run->user?->name ?? ($run->visitor ? 'Visitor' : 'Unknown'),
                'taskDescription' => substr($run->task_description ?? '', 0, 50),
                'status' => $run->workflow_stage,
                'createdAt' => $run->created_at->toIso8601String(),
                'completedAt' => $run->completed_at?->toIso8601String(),
                'durationMs' => $durationMs,
            ];
        })->values();

        // Count by workflow stage
        $stageBreakdown = [
            'processing' => $processingRuns,
            'completed' => $completedRuns,
            'failed' => $failedRuns,
        ];

        return response()->json([
            'stats' => [
                'totalRuns' => $totalRuns,
                'completedRuns' => $completedRuns,
                'failedRuns' => $failedRuns,
                'processingRuns' => $processingRuns,
                'successRate' => (float) $successRate,
                'avgDurationMs' => (int) $avgDurationMs,
            ],
            'stageBreakdown' => $stageBreakdown,
            'runs' => $runsData,
        ]);
    }

    /**
     * Get funnel analytics for a date
     */
    public function getFunnelAnalytics(Request $request): JsonResponse
    {
        $funnelSlug = $request->query('funnel', 'registration');
        $date = $request->query('date', now()->toDateString());
        $dateObj = Carbon::parse($date);

        $funnel = Funnel::where('slug', $funnelSlug)->with('stages')->first();

        if (! $funnel) {
            return response()->json([
                'error' => 'Funnel not found',
            ], 404);
        }

        // Get daily stats for this funnel on this date
        $stats = FunnelDailyStats::where('funnel_id', $funnel->id)
            ->where('date', $dateObj->toDateString())
            ->orderBy('stage')
            ->get();

        // Format stages with data
        $stagesData = [];
        foreach ($funnel->stages as $stage) {
            $stageStat = $stats->firstWhere('stage', $stage->order);

            $stagesData[] = [
                'stage' => (int) $stage->order,
                'stageName' => $stage->name,
                'starts' => (int) ($stageStat?->starts ?? 0),
                'conversions' => (int) ($stageStat?->conversions ?? 0),
                'conversionRate' => (float) ($stageStat?->conversion_rate ?? 0),
            ];
        }

        // Calculate overall funnel stats
        $firstStageStat = $stats->firstWhere('stage', 1);
        $lastStageStat = $stats->where('stage', '>', 1)->last();

        $totalEntered = (int) ($firstStageStat?->starts ?? 0);
        $totalConverted = (int) ($lastStageStat?->conversions ?? 0);
        $overallConversionRate = (float) ($totalEntered > 0 ? ($totalConverted / $totalEntered) * 100 : 0);

        // Get current state distribution
        $progressData = FunnelProgress::where('funnel_id', $funnel->id)
            ->select('stage', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->groupBy('stage')
            ->get();

        $stateDistribution = [];
        foreach ($funnel->stages as $stage) {
            $count = $progressData->firstWhere('stage', $stage->order)?->count ?? 0;
            $stateDistribution[] = [
                'stage' => $stage->order,
                'stageName' => $stage->name,
                'count' => $count,
            ];
        }

        return response()->json([
            'funnel' => [
                'id' => $funnel->id,
                'slug' => $funnel->slug,
                'name' => $funnel->name,
                'description' => $funnel->description,
            ],
            'stages' => $stagesData,
            'stats' => [
                'date' => $dateObj->toDateString(),
                'totalEntered' => $totalEntered,
                'totalConverted' => $totalConverted,
                'overallConversionRate' => $overallConversionRate,
            ],
            'stateDistribution' => $stateDistribution,
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
