<?php

namespace App\Jobs;

use App\Data\GenerationPayload;
use App\Events\PromptOptimizationCompleted;
use App\Models\PromptRun;
use App\Services\DatabaseService;
use App\Services\N8nWorkflowClient;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPromptGeneration implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PromptRun $promptRun,
        public ?string $database = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(N8nWorkflowClient $workflowClient): void
    {
        // Switch database if specified (e.g., for data collection tests)
        if ($this->database) {
            config(['database.connections.pgsql.database' => $this->database]);
            \DB::purge('pgsql');
        }

        try {
            Log::info('Processing prompt generation', [
                'prompt_run_id' => $this->promptRun->id,
                'database' => $this->database ?? config('database.connections.pgsql.database'),
            ]);

            // Combine questions with answers for workflow 2
            $questions = $this->promptRun->framework_questions ?? [];
            $answers = $this->promptRun->clarifying_answers ?? [];
            $questionAnswers = [];

            foreach ($questions as $index => $question) {
                $questionAnswers[] = [
                    'question' => is_array($question) ? ($question['question'] ?? '') : $question,
                    'answer' => $answers[$index] ?? '',
                ];
            }

            // Get user context for workflow optimisation (includes visitor fallback)
            $userContext = $this->promptRun->getUserContext();

            // Run the generation workflow
            $payload = new GenerationPayload(
                taskClassification: $this->promptRun->task_classification,
                cognitiveRequirements: $this->promptRun->cognitive_requirements ?? [],
                selectedFramework: $this->promptRun->selected_framework,
                personalityTier: $this->promptRun->personality_tier,
                taskTraitAlignment: $this->promptRun->task_trait_alignment ?? [],
                originalTaskDescription: $this->promptRun->task_description,
                questionAnswers: $questionAnswers,
                personalityType: $this->promptRun->personality_type,
                traitPercentages: $this->promptRun->trait_percentages,
                userContext: $userContext,
                preAnalysisContext: $this->promptRun->pre_analysis_context
            );

            $result = $workflowClient->executeGeneration($payload);

            // For async workflows, n8n returns immediately without results
            // The actual results come back via webhook callback
            // So we don't handle failures here - just leave the workflow_stage as 2_processing
            // and wait for the webhook to update it
            if (! $result['success']) {
                // Check if this is just "workflow queued" (async response) vs actual error
                $errorMsg = $result['error'] ?? $result['error']['message'] ?? 'Unknown error';

                // If n8n returned a 202 Accepted or similar (async), it's not really a failure
                // The actual result will come via webhook
                if (strpos($errorMsg, 'Accepted') !== false || strpos($errorMsg, 'queued') !== false) {
                    Log::info('Workflow 2 queued asynchronously', [
                        'prompt_run_id' => $this->promptRun->id,
                        'workflow_stage' => $this->promptRun->workflow_stage,
                    ]);

                    return;
                }

                $this->handleFailure(
                    $errorMsg,
                    $result
                );

                return;
            }

            // If we got actual results back (sync response), update immediately
            // Otherwise, wait for the webhook callback
            if (! isset($result['data'])) {
                Log::info('Workflow 2 returned empty data, waiting for webhook callback', [
                    'prompt_run_id' => $this->promptRun->id,
                ]);

                return;
            }

            // Update the prompt run with generation results
            DatabaseService::retryOnDeadlock(function () use ($result) {
                $this->promptRun->update([
                    'optimized_prompt' => $result['data']['optimised_prompt'] ?? null,
                    'framework_used' => $result['data']['framework_used'] ?? null,
                    'personality_adjustments_summary' => $result['data']['personality_adjustments_summary'] ?? null,
                    'model_recommendations' => $result['data']['model_recommendations'] ?? null,
                    'iteration_suggestions' => $result['data']['iteration_suggestions'] ?? null,
                    'generation_api_usage' => $result['api_usage'] ?? null,
                    'workflow_stage' => '2_completed',
                    'completed_at' => now(),
                    'error_message' => null,
                ]);
            });

            Log::info('Prompt generation completed', [
                'prompt_run_id' => $this->promptRun->id,
            ]);

            // Refresh the model to ensure we have the latest data
            $this->promptRun->refresh();

            // Broadcast generation completed event
            try {
                event(new PromptOptimizationCompleted($this->promptRun));
            } catch (Exception $e) {
                Log::error('Failed to broadcast PromptOptimizationCompleted event', [
                    'prompt_run_id' => $this->promptRun->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Exception in ProcessPromptGeneration job', [
                'prompt_run_id' => $this->promptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->handleFailure(
                'An error occurred whilst generating the prompt: '.$e->getMessage(),
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
        Log::error('Prompt generation workflow failed', [
            'prompt_run_id' => $this->promptRun->id,
            'error' => $errorMessage,
            'payload' => $errorPayload,
        ]);

        // Store the error details
        DatabaseService::retryOnDeadlock(function () use ($errorMessage) {
            $this->promptRun->update([
                'workflow_stage' => '2_failed',
                'error_message' => $errorMessage,
            ]);
        });
    }
}
