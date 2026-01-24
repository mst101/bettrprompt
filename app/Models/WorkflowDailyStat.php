<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowDailyStat extends Model
{
    protected $table = 'workflow_daily_stats';

    protected $fillable = [
        'date',
        'workflow_stage',
        'total_executions',
        'successful_executions',
        'failed_executions',
        'success_rate',
        'avg_duration_ms',
        'min_duration_ms',
        'max_duration_ms',
        'total_input_tokens',
        'total_output_tokens',
        'total_cost_usd',
        'avg_cost_per_execution',
        'retries',
        'retry_rate',
        'top_errors',
    ];

    protected $casts = [
        'date' => 'date',
        'top_errors' => 'json',
    ];

    /**
     * Scope: filter by workflow stage
     */
    public function scopeByStage($query, int $stage)
    {
        return $query->where('workflow_stage', $stage);
    }

    /**
     * Scope: filter by date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope: filter by minimum success rate
     */
    public function scopeMinimumSuccessRate($query, float $rate)
    {
        return $query->where('success_rate', '>=', $rate);
    }

    /**
     * Scope: filter by maximum cost
     */
    public function scopeMaximumCost($query, float $maxCost)
    {
        return $query->where('avg_cost_per_execution', '<=', $maxCost);
    }

    /**
     * Get aggregated stats for a date range
     */
    public static function aggregateForRange($startDate, $endDate, ?int $stage = null)
    {
        $query = self::whereBetween('date', [$startDate, $endDate]);

        if ($stage !== null) {
            $query->where('workflow_stage', $stage);
        }

        return $query->get()->groupBy('workflow_stage')->map(function ($stats) {
            $totalExecutions = $stats->sum('total_executions');

            return [
                'total_executions' => $totalExecutions,
                'successful_executions' => $stats->sum('successful_executions'),
                'failed_executions' => $stats->sum('failed_executions'),
                'success_rate' => $totalExecutions > 0 ? $stats->sum('successful_executions') / $totalExecutions : 0,
                'avg_duration_ms' => $stats->avg('avg_duration_ms'),
                'total_cost_usd' => $stats->sum('total_cost_usd'),
                'avg_cost_per_execution' => $stats->avg('avg_cost_per_execution'),
                'total_retries' => $stats->sum('retries'),
                'avg_retry_rate' => $stats->avg('retry_rate'),
                'total_input_tokens' => $stats->sum('total_input_tokens'),
                'total_output_tokens' => $stats->sum('total_output_tokens'),
            ];
        });
    }

    /**
     * Determine workflow health based on metrics
     */
    public function isHealthy(): bool
    {
        // A workflow is healthy if:
        // - Success rate >= 95%
        // - Retry rate <= 10%
        // - Average cost is reasonable (varies by stage)
        return ($this->success_rate ?? 0) >= 0.95
            && ($this->retry_rate ?? 0) <= 0.10;
    }

    /**
     * Get most common error
     */
    public function getMostCommonError(): ?array
    {
        if ($this->top_errors && is_array($this->top_errors)) {
            return array_first($this->top_errors);
        }

        return null;
    }
}
