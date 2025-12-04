<?php

namespace App\Jobs;

use App\Events\PromptOptimizationCompleted;
use App\Models\PromptRun;
use App\Services\DatabaseService;
use App\Services\PromptFrameworkService;
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
    public function handle(PromptFrameworkService $promptService): void
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

            // Run the generation workflow
            $result = $promptService->generatePrompt(
                $this->promptRun->task_classification,
                $this->promptRun->cognitive_requirements ?? [],
                $this->promptRun->selected_framework,
                $this->promptRun->personality_tier,
                $this->promptRun->task_trait_alignment ?? [],
                $this->promptRun->personality_adjustments_preview ?? [],
                $this->promptRun->task_description,
                $this->promptRun->personality_type,
                $this->promptRun->trait_percentages,
                $questionAnswers
            );

            if (! $result['success']) {
                $this->handleFailure(
                    $result['error']['message'] ?? 'Generation workflow failed',
                    $result
                );

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
                    'status' => 'completed',
                    'workflow_stage' => 'completed',
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
                'status' => 'failed',
                'workflow_stage' => 'failed',
                'error_message' => $errorMessage,
            ]);
        });
    }
}
