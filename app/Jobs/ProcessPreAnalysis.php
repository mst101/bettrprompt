<?php

namespace App\Jobs;

use App\Events\PreAnalysisCompleted;
use App\Models\PromptRun;
use App\Services\DatabaseService;
use App\Services\PromptFrameworkService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPreAnalysis implements ShouldQueue
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
            Log::info('Processing pre-analysis', [
                'prompt_run_id' => $this->promptRun->id,
                'database' => $this->database ?? config('database.connections.pgsql.database'),
            ]);

            // Get user context for workflow optimisation
            $userContext = $this->promptRun->getUserContext();

            // Run the pre-analysis workflow (Workflow 0)
            $preAnalysis = $promptService->preAnalyseTask(
                $this->promptRun->task_description,
                $userContext
            );

            // Check if pre-analysis needs clarification questions
            if ($preAnalysis['needs_clarification']) {
                // Update with questions - user will answer them
                DatabaseService::retryOnDeadlock(function () use ($preAnalysis) {
                    $this->promptRun->update([
                        'status' => 'pending',
                        'workflow_stage' => 'pre_analysis_questions',
                        'pre_analysis_questions' => $preAnalysis['questions'] ?? [],
                        'pre_analysis_reasoning' => $preAnalysis['reasoning'] ?? null,
                        'pre_analysis_api_usage' => $preAnalysis['api_usage'] ?? null,
                        'error_message' => null,
                    ]);
                });

                Log::info('Pre-analysis completed with questions', [
                    'prompt_run_id' => $this->promptRun->id,
                    'question_count' => count($preAnalysis['questions'] ?? []),
                ]);

                // Refresh the model to ensure we have the latest data
                $this->promptRun->refresh();

                // Broadcast pre-analysis completed event
                try {
                    // Add a small delay to ensure client has subscribed to the channel
                    // This helps with race conditions where the event broadcasts before subscription
                    usleep(500000); // 0.5 seconds

                    Log::info('Dispatching PreAnalysisCompleted event', [
                        'prompt_run_id' => $this->promptRun->id,
                        'workflow_stage' => $this->promptRun->workflow_stage,
                    ]);
                    event(new PreAnalysisCompleted($this->promptRun));
                    Log::info('PreAnalysisCompleted event dispatched successfully', [
                        'prompt_run_id' => $this->promptRun->id,
                    ]);
                } catch (Exception $e) {
                    Log::error('Failed to broadcast PreAnalysisCompleted event', [
                        'prompt_run_id' => $this->promptRun->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                // No questions needed - proceed directly to main analysis
                DatabaseService::retryOnDeadlock(function () use ($preAnalysis) {
                    $this->promptRun->update([
                        'status' => 'processing',
                        'workflow_stage' => 'submitted',
                        'pre_analysis_skipped' => true,
                        'pre_analysis_reasoning' => $preAnalysis['reasoning'] ?? null,
                        'pre_analysis_context' => $preAnalysis['pre_analysis_context'] ?? null,
                        'pre_analysis_api_usage' => $preAnalysis['api_usage'] ?? null,
                        'error_message' => null,
                    ]);
                });

                Log::info('Pre-analysis skipped - proceeding to main analysis', [
                    'prompt_run_id' => $this->promptRun->id,
                ]);

                // Refresh the model
                $this->promptRun->refresh();

                // Dispatch main analysis job (Workflow 1)
                ProcessTaskAnalysis::dispatch($this->promptRun, null, $this->database);
            }
        } catch (Exception $e) {
            Log::error('Exception in ProcessPreAnalysis job', [
                'prompt_run_id' => $this->promptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->handleFailure(
                'An error occurred whilst generating Quick Queries: '.$e->getMessage(),
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
        Log::error('Pre-analysis workflow failed', [
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
