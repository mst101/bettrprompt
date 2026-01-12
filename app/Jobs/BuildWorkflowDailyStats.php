<?php

namespace App\Jobs;

use App\Models\WorkflowAnalytic;
use App\Models\WorkflowDailyStat;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BuildWorkflowDailyStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  Carbon|null  $date  Date to aggregate for (defaults to yesterday)
     */
    public function __construct(
        private ?Carbon $date = null,
    ) {
        $this->date = $this->date ?? now()->subDay()->startOfDay();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Building workflow daily stats', [
                'date' => $this->date->toDateString(),
            ]);

            $dayStart = $this->date->clone()->startOfDay();
            $dayEnd = $this->date->clone()->endOfDay();

            // Get all workflow analytics for the day
            $analytics = WorkflowAnalytic::whereBetween('started_at', [$dayStart, $dayEnd])
                ->get();

            if ($analytics->isEmpty()) {
                Log::info('No workflow analytics for date', ['date' => $this->date->toDateString()]);

                return;
            }

            // Group by workflow_stage and calculate stats
            $byStage = $analytics->groupBy('workflow_stage');

            foreach ($byStage as $stage => $stageAnalytics) {
                $total = $stageAnalytics->count();
                $successful = $stageAnalytics->where('status', 'completed')->count();
                $failed = $stageAnalytics->where('status', 'failed')->count();
                $timedOut = $stageAnalytics->where('status', 'timeout')->count();

                // Calculate success rate
                $successRate = $total > 0 ? ($successful / $total) : 0;

                // Calculate timing
                $avgDuration = $stageAnalytics->where('status', 'completed')
                    ->whereNotNull('duration_ms')
                    ->avg('duration_ms');

                $minDuration = $stageAnalytics->where('status', 'completed')
                    ->whereNotNull('duration_ms')
                    ->min('duration_ms');

                $maxDuration = $stageAnalytics->where('status', 'completed')
                    ->whereNotNull('duration_ms')
                    ->max('duration_ms');

                // Calculate costs
                $totalInputTokens = $stageAnalytics->sum('input_tokens') ?? 0;
                $totalOutputTokens = $stageAnalytics->sum('output_tokens') ?? 0;
                $totalCost = $stageAnalytics->sum('estimated_cost_usd') ?? 0;
                $avgCost = $successful > 0 ? ($totalCost / $successful) : 0;

                // Calculate retry rate
                $retries = $stageAnalytics->where('was_retry', true)->count();
                $retryRate = $total > 0 ? ($retries / $total) : 0;

                // Get top errors
                $topErrors = $stageAnalytics->where('status', 'failed')
                    ->groupBy('error_code')
                    ->map(function ($errors) {
                        return [
                            'error_code' => $errors->first()->error_code,
                            'count' => $errors->count(),
                            'percentage' => 0, // Will be calculated below
                        ];
                    })
                    ->sortByDesc('count')
                    ->take(5)
                    ->values();

                // Add percentage to top errors
                if ($topErrors->isNotEmpty() && $failed > 0) {
                    $topErrors = $topErrors->map(function ($error) use ($failed) {
                        return [
                            'error_code' => $error['error_code'],
                            'count' => $error['count'],
                            'percentage' => ($error['count'] / $failed) * 100,
                        ];
                    })->values();
                }

                $stat = WorkflowDailyStat::updateOrCreate(
                    [
                        'date' => $this->date->toDateString(),
                        'workflow_stage' => $stage,
                    ],
                    [
                        'total_executions' => $total,
                        'successful_executions' => $successful,
                        'failed_executions' => $failed,
                        'success_rate' => $successRate,
                        'avg_duration_ms' => $avgDuration,
                        'min_duration_ms' => $minDuration,
                        'max_duration_ms' => $maxDuration,
                        'total_input_tokens' => $totalInputTokens,
                        'total_output_tokens' => $totalOutputTokens,
                        'total_cost_usd' => $totalCost,
                        'avg_cost_per_execution' => $avgCost,
                        'retries' => $retries,
                        'retry_rate' => $retryRate,
                        'top_errors' => $topErrors->isNotEmpty() ? $topErrors->toArray() : null,
                    ],
                );

                Log::info('Workflow daily stat created/updated', [
                    'workflow_stage' => $stage,
                    'date' => $this->date->toDateString(),
                    'total_executions' => $total,
                    'success_rate' => round($successRate * 100, 2).'%',
                    'total_cost_usd' => round($totalCost, 4),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to build workflow daily stats', [
                'date' => $this->date->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
