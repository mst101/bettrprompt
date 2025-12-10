<?php

namespace App\Http\Controllers;

use App\Events\WorkflowFailed;
use App\Models\PromptRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Mock n8n webhook endpoints for E2E testing
 * These endpoints simulate n8n responses without requiring n8n to be running
 */
class MockN8nController extends Controller
{
    /**
     * Get the mock scenario from file storage (set by test API endpoints)
     */
    protected function getMockScenario(Request $request): ?string
    {
        // First check the request header (for backward compatibility)
        $scenario = $request->header('X-Mock-Scenario');
        if ($scenario) {
            return $scenario;
        }

        // Then check environment variable (set by test setup)
        $scenario = env('TEST_MOCK_SCENARIO');
        if ($scenario) {
            return $scenario;
        }

        // Then check file-based storage (set by N8nMockService API endpoint)
        $scenarioFile = storage_path('app/test_mock_scenario.txt');
        if (file_exists($scenarioFile)) {
            $scenario = trim(file_get_contents($scenarioFile));
            if ($scenario) {
                return $scenario;
            }
        }

        return null;
    }

    /**
     * Handle error scenario and update database
     */
    protected function handleErrorScenario(Request $request, string $scenario, string $workflowStage): JsonResponse
    {
        $promptRunId = $request->input('prompt_run_id');
        $errorMessages = [
            'rate-limit' => 'Rate limit exceeded. Please wait before retrying. Try again in 60 seconds.',
            'timeout' => 'Workflow processing timed out. Please try again.',
            'api-error' => 'External service unavailable. Please try again later.',
            'validation-error' => 'Invalid input provided. Please check your task description.',
        ];

        $errorMessage = $errorMessages[$scenario] ?? 'Workflow processing failed.';

        // Update the prompt run with the failure
        if ($promptRunId) {
            try {
                $failureStage = str_replace('_processing', '_failed', $workflowStage);
                $promptRun = PromptRun::find($promptRunId);

                if ($promptRun) {
                    $promptRun->update([
                        'workflow_stage' => $failureStage,
                        'status' => 'failed',
                        'error_message' => $errorMessage,
                    ]);

                    // Broadcast the failure event
                    event(new WorkflowFailed($promptRun));

                    Log::info('Mock workflow error simulated', [
                        'prompt_run_id' => $promptRunId,
                        'scenario' => $scenario,
                        'workflow_stage' => $failureStage,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to handle mock error scenario', [
                    'prompt_run_id' => $promptRunId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'error' => true,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Mock pre-analysis webhook (workflow_0_pre_analysis)
     * Returns mock response indicating no clarification needed
     */
    public function preAnalysis(Request $request): JsonResponse
    {
        $scenario = $this->getMockScenario($request);

        Log::info('MockN8nController::preAnalysis called', [
            'scenario' => $scenario,
            'headers' => $request->headers->all(),
        ]);

        // Check if this is an error scenario
        if ($scenario && in_array($scenario, ['rate-limit', 'timeout', 'api-error', 'validation-error'])) {
            return $this->handleErrorScenario($request, $scenario, '0_processing');
        }

        $taskDescription = $request->input('task_description', '');

        // For E2E tests, always skip pre-analysis questions
        // This allows tests to proceed directly to the main workflow
        return response()->json([
            'success' => true,
            'data' => [
                'needs_clarification' => false,
                'reasoning' => 'Mock: Proceeding directly to analysis for E2E testing.',
            ],
        ]);
    }

    /**
     * Mock analysis webhook (workflow_1)
     * Returns mock framework selection and questions
     */
    public function analyse(Request $request): JsonResponse
    {
        $scenario = $this->getMockScenario($request);

        // Check if this is an error scenario
        if ($scenario && in_array($scenario, ['rate-limit', 'timeout', 'api-error', 'validation-error'])) {
            return $this->handleErrorScenario($request, $scenario, '1_processing');
        }

        // This would be called by the ProcessAnalysis job
        // For now, we don't mock this as the job runs asynchronously
        // and E2E tests check for loading states, not completion

        return response()->json([
            'success' => true,
            'message' => 'Mock: Analysis queued for processing',
        ]);
    }

    /**
     * Mock prompt optimisation webhook (workflow_2)
     * Returns mock optimised prompt
     */
    public function optimisePrompt(Request $request): JsonResponse
    {
        $scenario = $this->getMockScenario($request);

        // Check if this is an error scenario
        if ($scenario && in_array($scenario, ['rate-limit', 'timeout', 'api-error', 'validation-error'])) {
            return $this->handleErrorScenario($request, $scenario, '2_processing');
        }

        return response()->json([
            'success' => true,
            'message' => 'Mock: Prompt optimisation queued for processing',
        ]);
    }
}
