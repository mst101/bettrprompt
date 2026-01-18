<?php

namespace App\Http\Controllers;

use App\Events\AnalysisCompleted;
use App\Events\PromptOptimizationCompleted;
use App\Models\AnalyticsEvent;
use App\Models\PromptRun;
use App\Models\Visitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Test-only controller for triggering broadcast events in E2E tests
 *
 * This controller allows E2E tests to manually trigger WebSocket events
 * so we can test real-time update functionality without waiting for
 * asynchronous n8n workflows to complete.
 *
 * SECURITY: Only accessible with X-Test-Auth header (same as test login)
 */
class TestBroadcastController extends Controller
{
    /**
     * Trigger an AnalysisCompleted event for a prompt run
     *
     * This simulates the event that fires when n8n completes framework selection
     */
    public function triggerAnalysisCompleted(Request $request, int $promptRunId): JsonResponse
    {
        // Security check
        if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
            abort(403, 'Unauthorised');
        }

        $promptRun = PromptRun::findOrFail($promptRunId);

        // Update the prompt run to simulate completed analysis (1_completed)
        if (! $promptRun->selected_framework) {
            $promptRun->update([
                'selected_framework' => [
                    'name' => 'SMART Goals',
                    'code' => 'SMART',
                    'components' => [
                        'Specific - Clear and well-defined objectives',
                        'Measurable - Quantifiable success metrics',
                        'Achievable - Realistic with available resources',
                        'Relevant - Aligned with broader goals',
                        'Time-bound - Specific deadline or timeline',
                    ],
                    'rationale' => 'Ideal for goal-setting, project planning, and outcome-focused tasks',
                ],
                'framework_questions' => [
                    'What is the specific goal you want to achieve?',
                    'How will you measure success?',
                    'What is your timeline for achieving this goal?',
                ],
                'workflow_stage' => '1_completed',
            ]);

            $promptRun->refresh();
        }

        // Broadcast the event
        event(new AnalysisCompleted($promptRun));

        return response()->json([
            'success' => true,
            'message' => 'AnalysisCompleted event broadcasted',
            'data' => [
                'prompt_run_id' => $promptRun->id,
                'selected_framework' => $promptRun->selected_framework,
                'workflow_stage' => $promptRun->workflow_stage,
            ],
        ]);
    }

    /**
     * Trigger a PromptOptimizationCompleted event for a prompt run
     *
     * This simulates the event that fires when n8n completes prompt optimisation
     */
    public function triggerPromptOptimizationCompleted(Request $request, int $promptRunId): JsonResponse
    {
        // Security check
        if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
            abort(403, 'Unauthorised');
        }

        $promptRun = PromptRun::findOrFail($promptRunId);

        // Ensure framework is selected first
        if (! $promptRun->selected_framework) {
            return response()->json([
                'success' => false,
                'error' => 'Framework must be selected before prompt optimisation can be completed',
                'hint' => 'Call triggerAnalysisCompleted first',
            ], 422);
        }

        // Update the prompt run to simulate completed optimisation (2_completed)
        if (! $promptRun->optimized_prompt) {
            $promptRun->update([
                'workflow_stage' => '2_completed',
                'optimized_prompt' => "# Test Optimised Prompt\n\nThis is a test prompt generated for E2E testing purposes.\n\n## Your Task\n$promptRun->task_description\n\n## Recommended Framework\n$promptRun->selected_framework\n\nPlease proceed with this structured approach to achieve the best results.",
                'completed_at' => now(),
            ]);

            $promptRun->refresh();
        }

        // Broadcast the event
        event(new PromptOptimizationCompleted($promptRun));

        return response()->json([
            'success' => true,
            'message' => 'PromptOptimizationCompleted event broadcasted',
            'data' => [
                'prompt_run_id' => $promptRun->id,
                'workflow_stage' => $promptRun->workflow_stage,
                'completed_at' => $promptRun->completed_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get information about WebSocket/Echo connection for debugging
     */
    public function echoInfo(Request $request): JsonResponse
    {
        // Security check
        if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
            abort(403, 'Unauthorised');
        }

        return response()->json([
            'reverb_enabled' => config('broadcasting.default') === 'reverb',
            'reverb_host' => config('broadcasting.connections.reverb.options.host'),
            'reverb_port' => config('broadcasting.connections.reverb.options.port'),
            'app_key' => config('broadcasting.connections.reverb.key'),
            'environment' => app()->environment(),
        ]);
    }

    /**
     * Create a test prompt run in a specific workflow stage for testing
     *
     * Workflow stages supported:
     * - '0_processing': Pre-analysis in progress
     * - '0_completed': Pre-analysis complete with quick queries
     * - '0_failed': Pre-analysis failed
     * - '1_processing': Main analysis in progress, no framework selected
     * - '1_completed': Framework selected, no optimised prompt
     * - '1_failed': Main analysis failed
     * - '2_processing': Prompt optimisation in progress
     * - '2_completed': Full workflow completed with optimised prompt
     * - '2_failed': Prompt optimisation failed
     */
    public function createTestPromptRun(Request $request): JsonResponse
    {
        // Security check
        if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
            abort(403, 'Unauthorised');
        }

        $state = $request->query('state', '1_processing');
        $userId = auth()->id();

        $visitorId = $request->cookie('visitor_id');
        $visitor = $visitorId ? Visitor::find($visitorId) : null;

        if (! $visitor) {
            $visitor = Visitor::create();
        }

        $data = [
            'visitor_id' => $visitor->id,
            'user_id' => $userId,
            'task_description' => "E2E Test Prompt - Workflow Stage: $state",
            'task_classification' => ['type' => 'prompt_builder', 'source' => 'test'],
            'personality_type' => 'INTJ-A',
        ];

        if (in_array($state, ['0_processing', '0_failed'])) {
            $data['workflow_stage'] = $state;
            if ($state === '0_failed') {
                $data['error_message'] = 'Test pre-analysis failure for E2E testing';
            }
        } elseif ($state === '0_completed') {
            $data['workflow_stage'] = '0_completed';
        } elseif ($state === '1_processing') {
            $data['workflow_stage'] = '1_processing';
        } elseif ($state === '1_completed') {
            $data['workflow_stage'] = '1_completed';
            $data['selected_framework'] = [
                'name' => 'SMART Goals',
                'code' => 'SMART',
                'components' => [
                    'Specific - Clear and well-defined objectives',
                    'Measurable - Quantifiable success metrics',
                    'Achievable - Realistic with available resources',
                    'Relevant - Aligned with broader goals',
                    'Time-bound - Specific deadline or timeline',
                ],
                'rationale' => 'Ideal for goal-setting, project planning, and outcome-focused tasks',
            ];
            $data['framework_questions'] = [
                'What is the specific goal you want to achieve?',
                'How will you measure success?',
                'What is your timeline for achieving this goal?',
            ];
            // Add pre-analysis questions for testing
            $data['pre_analysis_questions'] = [
                [
                    'id' => 'task-clarity',
                    'question' => 'Is your task clear and well-defined?',
                    'type' => 'yes_no',
                    'options' => [
                        ['value' => 'yes', 'label' => 'Yes'],
                        ['value' => 'no', 'label' => 'No'],
                    ],
                    'allowsOther' => false,
                ],
            ];
            // Add alternative frameworks for testing
            $data['alternative_frameworks'] = [
                [
                    'name' => 'STAR',
                    'code' => 'STAR',
                    'when_to_use_instead' => 'For storytelling-based feedback',
                ],
                [
                    'name' => 'RISE',
                    'code' => 'RISE',
                    'when_to_use_instead' => 'For structured improvement plans',
                ],
            ];
        } elseif ($state === '1_failed') {
            $data['workflow_stage'] = '1_failed';
            $data['error_message'] = 'Test main analysis failure for E2E testing';
        } elseif ($state === '2_processing') {
            $data['workflow_stage'] = '2_processing';
            $data['selected_framework'] = [
                'name' => 'SMART Goals',
                'code' => 'SMART',
                'components' => [
                    'Specific - Clear and well-defined objectives',
                    'Measurable - Quantifiable success metrics',
                    'Achievable - Realistic with available resources',
                    'Relevant - Aligned with broader goals',
                    'Time-bound - Specific deadline or timeline',
                ],
                'rationale' => 'Ideal for goal-setting, project planning, and outcome-focused tasks',
            ];
            $data['framework_questions'] = [
                'What is the specific goal you want to achieve?',
                'How will you measure success?',
                'What is your timeline for achieving this goal?',
            ];
        } elseif ($state === '2_completed') {
            $data['workflow_stage'] = '2_completed';
            $data['selected_framework'] = [
                'name' => 'SMART Goals',
                'code' => 'SMART',
                'components' => [
                    'Specific - Clear and well-defined objectives',
                    'Measurable - Quantifiable success metrics',
                    'Achievable - Realistic with available resources',
                    'Relevant - Aligned with broader goals',
                    'Time-bound - Specific deadline or timeline',
                ],
                'rationale' => 'Ideal for goal-setting, project planning, and outcome-focused tasks',
            ];
            $data['framework_questions'] = [
                'What is the specific goal you want to achieve?',
                'How will you measure success?',
                'What is your timeline for achieving this goal?',
            ];
            $data['optimized_prompt'] = 'This is a test optimised prompt.';
            $data['completed_at'] = now();
        } elseif ($state === '2_failed') {
            $data['workflow_stage'] = '2_failed';
            $data['error_message'] = 'Test prompt optimisation failure for E2E testing';
            $data['selected_framework'] = [
                'name' => 'SMART Goals',
                'code' => 'SMART',
                'components' => [
                    'Specific - Clear and well-defined objectives',
                    'Measurable - Quantifiable success metrics',
                    'Achievable - Realistic with available resources',
                    'Relevant - Aligned with broader goals',
                    'Time-bound - Specific deadline or timeline',
                ],
                'rationale' => 'Ideal for goal-setting, project planning, and outcome-focused tasks',
            ];
            $data['framework_questions'] = [
                'What is the specific goal you want to achieve?',
                'How will you measure success?',
                'What is your timeline for achieving this goal?',
            ];
        }

        $promptRun = PromptRun::create($data);

        return response()->json([
            'success' => true,
            'prompt_run_id' => $promptRun->id,
            'visitor_id' => $visitor->id,
            'state' => $state,
            'url' => "/prompt-builder/$promptRun->id",
        ])->cookie('visitor_id', $visitor->id);
    }

    /**
     * Set personality type for the currently authenticated user
     *
     * This allows E2E tests to quickly configure a user with a specific personality
     * without going through the UI form manually
     */
    public function setPersonalityType(Request $request): JsonResponse
    {
        // Security check
        if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
            abort(403, 'Unauthorised');
        }

        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'User must be authenticated',
            ], 401);
        }

        $user = Auth::user();
        $personalityType = $request->input('personality_type');
        $identity = $request->input('identity', 'assertive');
        $traits = $request->input('traits', [
            'mind' => 50,
            'energy' => 50,
            'nature' => 50,
            'tactics' => 50,
            'identity' => 50,
        ]);

        // Create personality type code (e.g., "INTJ-A")
        $personalityCode = "$personalityType-".($identity === 'assertive' ? 'A' : 'T');

        // Update user personality with the trait percentages as sent by the fixture
        $user->update([
            'personality_type' => $personalityCode,
            'trait_percentages' => [
                'mind' => $traits['mind'] ?? 50,
                'energy' => $traits['energy'] ?? 50,
                'nature' => $traits['nature'] ?? 50,
                'tactics' => $traits['tactics'] ?? 50,
                'identity' => $traits['identity'] ?? 50,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Personality type set successfully',
            'data' => [
                'personality_type' => $user->personality_type,
                'trait_percentages' => $user->trait_percentages,
            ],
        ]);
    }

    /**
     * Get analytics events for testing
     *
     * Allows E2E tests to verify that analytics events were properly tracked
     *
     * Query parameters:
     * - event_name: Filter by event name (e.g., 'tab_viewed', 'question_answered')
     * - prompt_run_id: Filter by prompt run ID
     * - page_path: Filter by page path
     * - limit: Maximum number of events to return (default: 100)
     */
    public function getAnalyticsEvents(Request $request): JsonResponse
    {
        // Security check
        if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
            abort(403, 'Unauthorised');
        }

        $query = AnalyticsEvent::query();

        // Filter by event name
        if ($request->has('event_name')) {
            $query->where('name', $request->query('event_name'));
        }

        // Filter by prompt run ID
        if ($request->has('prompt_run_id')) {
            $query->where('prompt_run_id', $request->query('prompt_run_id'));
        }

        // Filter by page path
        if ($request->has('page_path')) {
            $query->where('page_path', $request->query('page_path'));
        }

        // Order by most recent first
        $query->orderBy('occurred_at', 'desc');

        // Limit results
        $limit = $request->query('limit', 100);
        $query->limit($limit);

        $events = $query->get();

        return response()->json($events);
    }
}
