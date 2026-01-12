<?php

namespace App\Services;

use App\Models\PromptRun;
use App\Models\WorkflowAnalytic;
use Illuminate\Support\Facades\Log;

class WorkflowAnalyticsService
{
    /**
     * Record workflow started event
     *
     * Called when a workflow begins execution
     */
    public function recordStart(
        PromptRun $promptRun,
        int $workflowStage,
        ?string $workflowVersion = null,
    ): WorkflowAnalytic {
        try {
            $analytic = WorkflowAnalytic::create([
                'prompt_run_id' => $promptRun->id,
                'workflow_stage' => $workflowStage,
                'workflow_version' => $workflowVersion,
                'started_at' => now(),
                'status' => 'processing',
                'attempt_number' => 1,
                'was_retry' => false,
            ]);

            Log::info('Workflow execution started', [
                'prompt_run_id' => $promptRun->id,
                'workflow_stage' => $workflowStage,
                'analytic_id' => $analytic->id,
            ]);

            return $analytic;
        } catch (\Exception $e) {
            Log::error('Failed to record workflow start', [
                'prompt_run_id' => $promptRun->id,
                'workflow_stage' => $workflowStage,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Record workflow completion
     *
     * Called when a workflow finishes successfully
     */
    public function recordCompletion(
        WorkflowAnalytic $analytic,
        ?int $inputTokens = null,
        ?int $outputTokens = null,
        ?float $estimatedCostUsd = null,
        ?string $modelUsed = null,
    ): WorkflowAnalytic {
        try {
            $completedAt = now();
            $durationMs = $analytic->started_at->diffInMilliseconds($completedAt);

            $analytic->update([
                'completed_at' => $completedAt,
                'duration_ms' => $durationMs,
                'status' => 'completed',
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'estimated_cost_usd' => $estimatedCostUsd,
                'model_used' => $modelUsed,
            ]);

            Log::info('Workflow execution completed', [
                'analytic_id' => $analytic->id,
                'workflow_stage' => $analytic->workflow_stage,
                'duration_ms' => $durationMs,
                'cost_usd' => $estimatedCostUsd,
            ]);

            return $analytic->refresh();
        } catch (\Exception $e) {
            Log::error('Failed to record workflow completion', [
                'analytic_id' => $analytic->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Record workflow failure
     *
     * Called when a workflow fails
     */
    public function recordFailure(
        WorkflowAnalytic $analytic,
        string $errorCode,
        string $errorMessage,
        ?int $inputTokens = null,
        ?int $outputTokens = null,
    ): WorkflowAnalytic {
        try {
            $failedAt = now();
            $durationMs = $analytic->started_at->diffInMilliseconds($failedAt);

            $analytic->update([
                'completed_at' => $failedAt,
                'duration_ms' => $durationMs,
                'status' => 'failed',
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
            ]);

            Log::warning('Workflow execution failed', [
                'analytic_id' => $analytic->id,
                'workflow_stage' => $analytic->workflow_stage,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
            ]);

            return $analytic->refresh();
        } catch (\Exception $e) {
            Log::error('Failed to record workflow failure', [
                'analytic_id' => $analytic->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Record workflow timeout
     *
     * Called when a workflow times out
     */
    public function recordTimeout(WorkflowAnalytic $analytic): WorkflowAnalytic
    {
        try {
            $timedOutAt = now();
            $durationMs = $analytic->started_at->diffInMilliseconds($timedOutAt);

            $analytic->update([
                'completed_at' => $timedOutAt,
                'duration_ms' => $durationMs,
                'status' => 'timeout',
                'error_code' => 'TIMEOUT',
                'error_message' => 'Workflow execution exceeded timeout limit',
            ]);

            Log::warning('Workflow execution timed out', [
                'analytic_id' => $analytic->id,
                'workflow_stage' => $analytic->workflow_stage,
            ]);

            return $analytic->refresh();
        } catch (\Exception $e) {
            Log::error('Failed to record workflow timeout', [
                'analytic_id' => $analytic->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Record workflow retry
     *
     * Called when a workflow is retried after failure
     */
    public function recordRetry(
        PromptRun $promptRun,
        int $workflowStage,
        int $attemptNumber,
    ): WorkflowAnalytic {
        try {
            $analytic = WorkflowAnalytic::create([
                'prompt_run_id' => $promptRun->id,
                'workflow_stage' => $workflowStage,
                'started_at' => now(),
                'status' => 'processing',
                'attempt_number' => $attemptNumber,
                'was_retry' => true,
            ]);

            Log::info('Workflow retry started', [
                'prompt_run_id' => $promptRun->id,
                'workflow_stage' => $workflowStage,
                'attempt_number' => $attemptNumber,
                'analytic_id' => $analytic->id,
            ]);

            return $analytic;
        } catch (\Exception $e) {
            Log::error('Failed to record workflow retry', [
                'prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get success rate for a workflow stage
     */
    public function getSuccessRate(int $workflowStage): float
    {
        $total = WorkflowAnalytic::where('workflow_stage', $workflowStage)->count();

        if ($total === 0) {
            return 0;
        }

        $successful = WorkflowAnalytic::where('workflow_stage', $workflowStage)
            ->where('status', 'completed')
            ->count();

        return ($successful / $total) * 100;
    }

    /**
     * Get average duration for a workflow stage
     */
    public function getAverageDuration(int $workflowStage): ?float
    {
        return WorkflowAnalytic::where('workflow_stage', $workflowStage)
            ->where('status', 'completed')
            ->whereNotNull('duration_ms')
            ->avg('duration_ms');
    }

    /**
     * Get total cost for a workflow stage (sum of all executions)
     */
    public function getTotalCost(int $workflowStage): float
    {
        return WorkflowAnalytic::where('workflow_stage', $workflowStage)
            ->whereNotNull('estimated_cost_usd')
            ->sum('estimated_cost_usd') ?? 0;
    }

    /**
     * Get average cost per execution for a workflow stage
     */
    public function getAverageCost(int $workflowStage): float
    {
        $successful = WorkflowAnalytic::where('workflow_stage', $workflowStage)
            ->where('status', 'completed')
            ->count();

        if ($successful === 0) {
            return 0;
        }

        return $this->getTotalCost($workflowStage) / $successful;
    }

    /**
     * Get retry rate for a workflow stage
     */
    public function getRetryRate(int $workflowStage): float
    {
        $total = WorkflowAnalytic::where('workflow_stage', $workflowStage)->count();

        if ($total === 0) {
            return 0;
        }

        $retries = WorkflowAnalytic::where('workflow_stage', $workflowStage)
            ->where('was_retry', true)
            ->count();

        return ($retries / $total) * 100;
    }

    /**
     * Get most common error for a workflow stage
     */
    public function getMostCommonError(int $workflowStage): ?array
    {
        $errors = WorkflowAnalytic::where('workflow_stage', $workflowStage)
            ->where('status', 'failed')
            ->selectRaw('error_code, count(*) as count')
            ->groupBy('error_code')
            ->orderByDesc('count')
            ->first();

        if (! $errors) {
            return null;
        }

        return [
            'error_code' => $errors->error_code,
            'count' => $errors->count,
        ];
    }

    /**
     * Get workflow stage health summary
     */
    public function getStageHealth(int $workflowStage): array
    {
        return [
            'workflow_stage' => $workflowStage,
            'total_executions' => WorkflowAnalytic::where('workflow_stage', $workflowStage)->count(),
            'successful' => WorkflowAnalytic::where('workflow_stage', $workflowStage)
                ->where('status', 'completed')
                ->count(),
            'failed' => WorkflowAnalytic::where('workflow_stage', $workflowStage)
                ->where('status', 'failed')
                ->count(),
            'timed_out' => WorkflowAnalytic::where('workflow_stage', $workflowStage)
                ->where('status', 'timeout')
                ->count(),
            'success_rate' => $this->getSuccessRate($workflowStage),
            'average_duration_ms' => $this->getAverageDuration($workflowStage),
            'average_cost_usd' => $this->getAverageCost($workflowStage),
            'retry_rate' => $this->getRetryRate($workflowStage),
            'most_common_error' => $this->getMostCommonError($workflowStage),
        ];
    }
}
