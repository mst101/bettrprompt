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
        // Check if this prompt_run exists in the data collection database
        // If it does, switch to that database for the duration of this job
        $currentDb = config('database.connections.pgsql.database');
        $dataCollectionDb = 'personality_data_collection';

        // Only check if we're currently on the main personality database
        $usingDataCollection = false;
        if ($currentDb === 'personality') {
            // Temporarily switch to check if the record exists in data collection DB
            config(['database.connections.pgsql.database' => $dataCollectionDb]);
            \DB::purge('pgsql');

            try {
                $exists = \DB::table('prompt_runs')
                    ->where('id', $this->promptRun->id)
                    ->exists();

                if ($exists) {
                    $usingDataCollection = true;
                    // Reload the model from the data collection database
                    $this->promptRun = PromptRun::find($this->promptRun->id);
                } else {
                    // Switch back to the original database
                    config(['database.connections.pgsql.database' => $currentDb]);
                    \DB::purge('pgsql');
                }
            } catch (\Exception $e) {
                // If data collection DB doesn't exist, switch back
                config(['database.connections.pgsql.database' => $currentDb]);
                \DB::purge('pgsql');
            }
        }

        try {
            Log::info('Processing task analysis', [
                'prompt_run_id' => $this->promptRun->id,
                'using_data_collection_db' => $usingDataCollection,
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
                    'framework_questions' => $this->sortClarifyingQuestions($result['data']['clarifying_questions'] ?? []),
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

    /**
     * Sort clarifying questions so required ones come first
     * Maintains logical flow by using a stable sort
     */
    protected function sortClarifyingQuestions(array $questions): array
    {
        // Separate required and optional questions, preserving order within each group
        $required = [];
        $optional = [];

        foreach ($questions as $question) {
            if (isset($question['required']) && $question['required'] === false) {
                $optional[] = $question;
            } else {
                $required[] = $question;
            }
        }

        // Merge with required questions first
        return array_merge($required, $optional);
    }
}
