<?php

namespace App\Jobs;

use App\Events\FrameworkSelected;
use App\Models\PromptRun;
use App\Services\DatabaseService;
use App\Services\N8nClient;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessFrameworkSelection implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PromptRun $promptRun,
        public array $payload
    ) {}

    /**
     * Execute the job.
     */
    public function handle(N8nClient $n8nClient): void
    {
        try {
            // Trigger framework selector workflow
            $response = $n8nClient->triggerWebhook(
                '/webhook/framework-selector',
                $this->payload
            );

            if ($response['success']) {
                $responseData = $response['data'];

                // Log the response for debugging
                Log::info('Framework Selector Response', [
                    'prompt_run_id' => $this->promptRun->id,
                    'response' => $responseData,
                ]);

                // Update the prompt run with framework selection
                DatabaseService::retryOnDeadlock(function () use ($responseData) {
                    $this->promptRun->update([
                        'selected_framework' => $responseData['selected_framework'] ?? null,
                        'framework_reasoning' => $responseData['framework_reasoning'] ?? null,
                        'personality_approach' => $responseData['personality_approach'] ?? null,
                        'framework_questions' => $responseData['framework_questions'] ?? [],
                        'clarifying_answers' => [],
                        'workflow_stage' => 'framework_selected',
                        'n8n_response_payload' => $responseData,
                    ]);
                });

                // Refresh the model to ensure we have the latest data
                $this->promptRun->refresh();

                // Log what was saved
                Log::info('Saved Framework Selection', [
                    'prompt_run_id' => $this->promptRun->id,
                    'selected_framework' => $this->promptRun->selected_framework,
                    'questions_count' => count($this->promptRun->framework_questions ?? []),
                    'framework_questions' => $this->promptRun->framework_questions,
                ]);

                // Broadcast framework selected event
                try {
                    event(new FrameworkSelected($this->promptRun));
                } catch (Exception $e) {
                    Log::error('Failed to broadcast FrameworkSelected event', [
                        'prompt_run_id' => $this->promptRun->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                // Handle n8n error
                $this->handleFailure(
                    $response['error'] ?? 'Framework selector workflow failed',
                    $response['payload'] ?? null
                );
            }
        } catch (Exception $e) {
            // Handle any exceptions
            Log::error('Exception in ProcessFrameworkSelection job', [
                'prompt_run_id' => $this->promptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->handleFailure(
                'An error occurred whilst selecting the framework: '.$e->getMessage(),
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
        Log::error('Framework selector workflow failed', [
            'prompt_run_id' => $this->promptRun->id,
            'error' => $errorMessage,
            'payload' => $errorPayload,
        ]);

        // Store the error details
        DatabaseService::retryOnDeadlock(function () use ($errorMessage, $errorPayload) {
            $this->promptRun->update([
                'status' => 'failed',
                'workflow_stage' => 'failed',
                'error_message' => $errorMessage,
                'n8n_response_payload' => $errorPayload,
            ]);
        });
    }
}
