<?php

namespace App\Jobs;

use App\Events\PromptOptimizationCompleted;
use App\Models\PromptRun;
use App\Services\DatabaseService;
use App\Services\N8nClient;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPromptOptimization implements ShouldQueue
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
            Log::info('Triggering final prompt optimization', [
                'prompt_run_id' => $this->promptRun->id,
                'selected_framework' => $this->promptRun->selected_framework,
            ]);

            // Trigger final prompt optimizer workflow
            $response = $n8nClient->triggerWebhook(
                '/webhook/final-prompt-optimizer',
                $this->payload
            );

            if ($response['success']) {
                $responseData = $response['data'];

                Log::info('Final prompt optimization completed', [
                    'prompt_run_id' => $this->promptRun->id,
                ]);

                // Update the prompt run with the final optimized prompt
                DatabaseService::retryOnDeadlock(function () use ($responseData) {
                    $this->promptRun->update([
                        'optimized_prompt' => $responseData['optimized_prompt'] ?? null,
                        'workflow_stage' => 'completed',
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                });

                // Broadcast completion event
                try {
                    event(new PromptOptimizationCompleted($this->promptRun));
                } catch (Exception $e) {
                    Log::error('Failed to broadcast PromptOptimizationCompleted event', [
                        'prompt_run_id' => $this->promptRun->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                // Handle n8n error
                $this->handleFailure(
                    $response['error'] ?? 'Final optimization workflow failed',
                    $response['payload'] ?? null
                );
            }
        } catch (Exception $e) {
            // Handle any exceptions
            Log::error('Exception in ProcessPromptOptimization job', [
                'prompt_run_id' => $this->promptRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->handleFailure(
                'An error occurred whilst generating the optimised prompt: '.$e->getMessage(),
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
        Log::error('Final prompt optimization failed', [
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
                'completed_at' => now(),
            ]);
        });
    }
}
