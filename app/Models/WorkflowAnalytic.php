<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAnalytic extends Model
{
    protected $fillable = [
        'prompt_run_id',
        'workflow_stage',
        'workflow_version',
        'started_at',
        'completed_at',
        'duration_ms',
        'status',
        'error_code',
        'error_message',
        'input_tokens',
        'output_tokens',
        'cost_usd',
        'model_used',
        'attempt_number',
        'was_retry',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'was_retry' => 'boolean',
    ];

    public function promptRun(): BelongsTo
    {
        return $this->belongsTo(PromptRun::class);
    }

    /**
     * Scope: filter by workflow stage (0, 1, or 2)
     */
    public function scopeByStage($query, int $stage)
    {
        return $query->where('workflow_stage', $stage);
    }

    /**
     * Scope: filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: successful executions only
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: failed executions only
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: timed out executions only
     */
    public function scopeTimedOut($query)
    {
        return $query->where('status', 'timeout');
    }

    /**
     * Scope: processing executions only
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope: retries only
     */
    public function scopeRetries($query)
    {
        return $query->where('was_retry', true);
    }

    /**
     * Scope: filter by duration range (in milliseconds)
     */
    public function scopeByDuration($query, int $minMs, int $maxMs)
    {
        return $query->whereBetween('duration_ms', [$minMs, $maxMs]);
    }

    /**
     * Scope: filter by cost range (in USD)
     */
    public function scopeByCost($query, float $minUsd, float $maxUsd)
    {
        return $query->whereBetween('cost_usd', [$minUsd, $maxUsd]);
    }

    /**
     * Scope: filter by model
     */
    public function scopeByModel($query, string $model)
    {
        return $query->where('model_used', $model);
    }

    /**
     * Scope: filter by time range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('started_at', [$startDate, $endDate]);
    }

    /**
     * Scope: filter by error code
     */
    public function scopeByErrorCode($query, string $errorCode)
    {
        return $query->where('error_code', $errorCode);
    }

    /**
     * Get average duration for this workflow
     */
    public function getAverageDuration(): ?float
    {
        return $this->successful()->avg('duration_ms');
    }

    /**
     * Get total cost for all attempts on this prompt run
     */
    public function getTotalCost(): float
    {
        return $this->sum('cost_usd') ?? 0;
    }

    /**
     * Check if workflow was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if workflow failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if workflow timed out
     */
    public function isTimedOut(): bool
    {
        return $this->status === 'timeout';
    }
}
