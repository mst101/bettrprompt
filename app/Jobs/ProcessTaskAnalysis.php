<?php

namespace App\Jobs;

use App\Events\AnalysisCompleted;
use App\Models\PromptRun;
use App\Services\DatabaseService;
use App\Services\PromptFrameworkService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessTaskAnalysis implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PromptRun $promptRun,
        public ?string $forcedFrameworkCode = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PromptFrameworkService $promptService): void
    {
        try {
            Log::info('Processing task analysis', [
                'prompt_run_id' => $this->promptRun->id,
            ]);

            // Run the analysis workflow (with optional forced framework)
            // Note: task_description may already be enhanced with pre-analysis
            // Pre-analysis context provides structured clarification data
            $result = $promptService->analyseTask(
                $this->promptRun->task_description,
                $this->promptRun->personality_type,
                $this->promptRun->trait_percentages,
                $this->promptRun->pre_analysis_context,
                $this->forcedFrameworkCode
            );

            if (! $result['success']) {
                $this->handleFailure(
                    $result['error']['message'] ?? 'Analysis workflow failed',
                    $result
                );

                return;
            }

            // Update the prompt run with analysis results
            DatabaseService::retryOnDeadlock(function () use ($result) {
                $this->promptRun->update([
                    'status' => 'pending',
                    'workflow_stage' => 'analysis_complete',
                    'task_classification' => $result['data']['task_classification'] ?? null,
                    'cognitive_requirements' => $result['data']['cognitive_requirements'] ?? null,
                    'selected_framework' => $result['data']['selected_framework'] ?? null,
                    'alternative_frameworks' => $result['data']['alternative_frameworks'] ?? [],
                    'personality_tier' => $result['data']['personality_tier'] ?? 'none',
                    'task_trait_alignment' => $result['data']['task_trait_alignment'] ?? null,
                    'personality_adjustments_preview' => $result['data']['personality_adjustments_preview'] ?? [],
                    'question_rationale' => $result['data']['question_rationale'] ?? null,
                    'framework_questions' => $result['data']['clarifying_questions'] ?? [],
                    'analysis_api_usage' => $result['api_usage'] ?? null,
                    'error_message' => null,
                ]);
            });

            Log::info('Task analysis completed', [
                'prompt_run_id' => $this->promptRun->id,
                'selected_framework' => $result['data']['selected_framework']['name'] ?? null,
            ]);

            // Refresh the model to ensure we have the latest data
            $this->promptRun->refresh();

            // Broadcast analysis completed event
            try {
                event(new AnalysisCompleted($this->promptRun));
            } catch (Exception $e) {
                Log::error('Failed to broadcast AnalysisCompleted event', [
                    'prompt_run_id' => $this->promptRun->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Exception in ProcessTaskAnalysis job', [
                'prompt_run_id' => $this->promptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->handleFailure(
                'An error occurred whilst analysing the task: '.$e->getMessage(),
                null
            );

            throw $e;
        }
    }

    /**
     * Handle job failure by updating the prompt run status
     */
    protected function handleFailure(string $errorMessage, ?array $errorPayload): void
    {
        Log::error('Task analysis workflow failed', [
            'prompt_run_id' => $this->promptRun->id,
            'error' => $errorMessage,
            'payload' => $errorPayload,
        ]);

        // Store the error details
        DatabaseService::retryOnDeadlock(function () use ($errorMessage) {
            $this->promptRun->update([
                'status' => 'failed',
                'workflow_stage' => 'failed',
                'error_message' => $errorMessage,
            ]);
        });
    }
}
