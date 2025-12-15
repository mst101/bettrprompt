<?php

namespace App\Http\Controllers;

use App\Events\AnalysisCompleted;
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
     * Mock workflow_0 (pre-analysis)
     * Returns mock pre-analysis context
     */
    public function workflow0(Request $request): JsonResponse
    {
        $scenario = $this->getMockScenario($request);

        Log::info('MockN8nController::workflow0 called', [
            'scenario' => $scenario,
            'headers' => $request->headers->all(),
        ]);

        // Check if this is an error scenario
        if ($scenario && in_array($scenario, ['rate-limit', 'timeout', 'api-error', 'validation-error'])) {
            return $this->handleErrorScenario($request, $scenario, '0_processing');
        }

        // For E2E tests, return no clarification needed
        // Tests will then go directly to the analysis workflow (workflow_1)
        // which will provide the framework and questions
        return response()->json([
            'success' => true,
            'data' => [
                'needs_clarification' => false,
                'reasoning' => 'Mock: Proceeding directly to analysis for E2E testing.',
            ],
        ]);
    }

    /**
     * Mock workflow_1 (analysis)
     * Returns mock framework selection and questions
     *
     * NOTE: This endpoint returns the analysis data synchronously.
     * The ProcessAnalysis job will read this response and update the database,
     * then broadcast the AnalysisCompleted event.
     */
    public function workflow1(Request $request): JsonResponse
    {
        $scenario = $this->getMockScenario($request);

        Log::info('MockN8nController::workflow1 called', [
            'scenario' => $scenario,
            'task_description' => $request->input('task_description'),
        ]);

        // Check if this is an error scenario
        if ($scenario && in_array($scenario, ['rate-limit', 'timeout', 'api-error', 'validation-error'])) {
            // For error scenarios, we still need to update the DB directly
            // because the error handling is different
            $promptRunId = $request->input('prompt_run_id');

            return $this->handleErrorScenario($request, $scenario, '1_processing');
        }

        // Return realistic framework data for E2E testing
        // ProcessAnalysis job will read this response and update the database
        $mockResponse = [
            'success' => true,
            'data' => [
                'task_classification' => [
                    'primary_category' => 'PROBLEM_SOLVING',
                    'secondary_category' => null,
                    'complexity' => 'moderate',
                    'classification_reasoning' => 'This task requires structured problem-solving with clear steps',
                    'content_type' => null,
                ],
                'cognitive_requirements' => [
                    'primary' => ['LOGICAL_THINKING', 'SYNTHESIS'],
                    'secondary' => ['ANALYSIS'],
                    'reasoning' => 'The task requires logical analysis and synthesis of information',
                ],
                'selected_framework' => [
                    'name' => 'STAR Method',
                    'code' => 'STAR',
                    'components' => ['Situation', 'Task', 'Action', 'Result'],
                    'rationale' => 'The STAR method is ideal for structuring complex narratives and demonstrating outcomes',
                ],
                'alternative_frameworks' => [
                    [
                        'name' => 'Problem-Solution-Benefit',
                        'code' => 'PSB',
                        'when_to_use_instead' => 'When emphasising business value and ROI',
                    ],
                ],
                'personality_tier' => 'full',
                'task_trait_alignment' => [
                    'amplified' => [
                        [
                            'trait' => 'Extraversion',
                            'requirement_aligned' => 'COMMUNICATION',
                            'reason' => 'Strong communicators excel at presenting ideas clearly',
                        ],
                    ],
                    'counterbalanced' => [
                        [
                            'trait' => 'Introversion',
                            'requirement_opposed' => 'AUDIENCE_ENGAGEMENT',
                            'reason' => 'May need encouragement to engage with audience',
                            'injection' => 'Include interactive elements and audience connection points',
                        ],
                    ],
                    'neutral' => [
                        [
                            'trait' => 'Thinking',
                            'reason' => 'Thinking preference does not directly impact this task',
                        ],
                    ],
                ],
                'personality_adjustments_preview' => [
                    'AMPLIFIED: Leverage clear, logical structure that appeals to analytical thinkers',
                    'COUNTERBALANCED: Add guidance on connecting with diverse audience perspectives',
                ],
                'clarifying_questions' => [
                    [
                        'id' => 'Q1',
                        'question' => 'What is the primary goal or objective you want to achieve?',
                        'purpose' => 'Understanding the end goal helps shape the framework',
                        'required' => true,
                    ],
                    [
                        'id' => 'Q2',
                        'question' => 'Who is your target audience for this?',
                        'purpose' => 'Audience knowledge helps tailor the tone and complexity',
                        'required' => true,
                    ],
                    [
                        'id' => 'Q3',
                        'question' => 'What constraints or limitations should we consider?',
                        'purpose' => 'Understanding constraints helps identify the most practical approach',
                        'required' => false,
                    ],
                ],
                'question_rationale' => 'These questions establish context, audience, and constraints which are essential for applying the STAR method effectively',
            ],
        ];

        return response()->json($mockResponse);
    }

    /**
     * Mock workflow_2 (generation/optimisation)
     * Returns mock optimised prompt
     */
    public function workflow2(Request $request): JsonResponse
    {
        $scenario = $this->getMockScenario($request);

        // Check if this is an error scenario
        if ($scenario && in_array($scenario, ['rate-limit', 'timeout', 'api-error', 'validation-error'])) {
            return $this->handleErrorScenario($request, $scenario, '2_processing');
        }

        return response()->json([
            'success' => true,
            'message' => 'Mock: Prompt generation queued for processing',
        ]);
    }
}
