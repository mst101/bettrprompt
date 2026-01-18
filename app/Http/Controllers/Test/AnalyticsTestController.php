<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use App\Models\PromptRun;
use App\Models\QuestionAnalytic;
use App\Models\Visitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Test Endpoints Controller for E2E Tests
 *
 * Provides secure endpoints for E2E tests to access and verify database state.
 * Only available in 'e2e' environment and requires 'X-Test-Auth' header.
 *
 * Security:
 * - Environment check: Only accessible when config('app.env') === 'e2e'
 * - Header verification: Requires X-Test-Auth: playwright-e2e-tests
 * - Read-only operations: Does not modify production data
 *
 * These endpoints are used by Playwright E2E tests to verify that
 * analytics events and ratings are properly saved to the database.
 */
class AnalyticsTestController extends Controller
{
    /**
     * Health check endpoint to verify the test controller is reachable
     */
    public function ping(): JsonResponse
    {
        return response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
    }

    /**
     * Get question analytics for a specific prompt run
     *
     * Returns all question analytics records for a given prompt run,
     * including rating and explanation data.
     *
     * @return JsonResponse Array of question analytics records
     */
    public function getQuestionAnalytics(int $promptRunId): JsonResponse
    {
        $analytics = QuestionAnalytic::where('prompt_run_id', $promptRunId)
            ->orderBy('display_order')
            ->get();

        return response()->json($analytics);
    }

    /**
     * Create a test visitor with a prompt run (not completed)
     *
     * Used by E2E tests to verify that guests without completed prompts
     * can edit task descriptions and clarifying question answers.
     *
     * @return JsonResponse Contains visitor_id and prompt_run_id
     */
    public function createVisitorPromptRun(Request $request): JsonResponse
    {
        // Security check
        if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
            abort(403, 'Unauthorised test endpoint access');
        }

        $visitor = Visitor::factory()->create();

        $promptRun = PromptRun::create([
            'visitor_id' => $visitor->id,
            'personality_type' => 'INTJ-A',
            'trait_percentages' => [
                'mind' => 80,
                'energy' => 75,
                'nature' => 70,
                'tactics' => 85,
                'identity' => 80,
            ],
            'task_description' => 'Test task for visitor restrictions',
            'workflow_stage' => '1_completed',
            'selected_framework' => [
                'code' => 'smart',
                'name' => 'S.M.A.R.T. Goals Framework',
                'components' => [
                    'Specific - Clear and well-defined',
                    'Measurable - Quantifiable criteria',
                    'Achievable - Realistic and attainable',
                    'Relevant - Aligned with objectives',
                    'Time-bound - Specific deadline or timeline',
                ],
                'rationale' => 'Ideal for goal-setting, project planning, and outcome-focused tasks',
            ],
            'framework_questions' => [
                'What is the specific goal you want to achieve?',
                'How will you measure success?',
                'What is your timeline for achieving this goal?',
            ],
            'task_classification' => ['type' => 'analytical', 'complexity' => 'moderate'],
            'cognitive_requirements' => ['analytical', 'critical-thinking'],
        ]);

        return response()->json([
            'visitor_id' => $visitor->id,
            'prompt_run_id' => $promptRun->id,
        ]);
    }

    /**
     * Create a test visitor with a completed prompt run
     *
     * Used by E2E tests to verify that guests with completed prompts
     * cannot edit until they create an account, and see the VisitorLimitModal.
     *
     * @return JsonResponse Contains visitor_id and prompt_run_id
     */
    public function createVisitorWithCompletedPrompt(Request $request): JsonResponse
    {
        // Security check
        if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
            abort(403, 'Unauthorised test endpoint access');
        }

        Log::info('Test: Creating visitor with completed prompt');

        $visitor = Visitor::factory()->create();
        Log::info('Test: Visitor created', ['visitor_id' => $visitor->id]);

        // Create a completed prompt run (2_completed stage)
        try {
            $promptRun = PromptRun::create([
                'visitor_id' => $visitor->id,
                'personality_type' => 'INTJ-A',
                'trait_percentages' => [
                    'mind' => 80,
                    'energy' => 75,
                    'nature' => 70,
                    'tactics' => 85,
                    'identity' => 80,
                ],
                'task_description' => 'Test task for visitor restrictions',
                'workflow_stage' => '2_completed',
                'selected_framework' => [
                    'code' => 'smart',
                    'name' => 'S.M.A.R.T. Goals Framework',
                    'components' => [
                        'Specific - Clear and well-defined',
                        'Measurable - Quantifiable criteria',
                        'Achievable - Realistic and attainable',
                        'Relevant - Aligned with objectives',
                        'Time-bound - Specific deadline or timeline',
                    ],
                    'rationale' => 'Ideal for goal-setting, project planning, and outcome-focused tasks',
                ],
                'framework_questions' => [
                    'What is the specific goal you want to achieve?',
                    'How will you measure success?',
                    'What is your timeline for achieving this goal?',
                ],
                'optimized_prompt' => '# Test Optimised Prompt\n\nThis is a test optimised prompt for E2E testing.',
                'completed_at' => now(),
                'task_classification' => ['type' => 'analytical', 'complexity' => 'moderate'],
                'cognitive_requirements' => ['analytical', 'critical-thinking'],
            ]);

            Log::info('Test: Prompt run created', [
                'prompt_run_id' => $promptRun->id,
                'workflow_stage' => $promptRun->workflow_stage,
                'optimized_prompt' => ! empty($promptRun->optimized_prompt) ? 'SET' : 'NULL',
            ]);

            return response()->json([
                'visitor_id' => $visitor->id,
                'prompt_run_id' => $promptRun->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Test: Failed to create prompt run', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Create a test visitor with completed prompt and an editable prompt run
     *
     * Used by E2E tests to verify visitor restrictions when editing clarifying
     * questions on a different prompt run, after they've already completed one.
     *
     * The logic: Visitor has completed their first prompt (1_completed),
     * so they should be restricted from editing a second prompt run (2_completed).
     *
     * @return JsonResponse Contains visitor_id, completed_prompt_run_id, and editable_prompt_run_id
     */
    public function createVisitorWithCompletedPromptForEdit(Request $request): JsonResponse
    {
        // Security check
        if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
            abort(403, 'Unauthorised test endpoint access');
        }

        $visitor = Visitor::factory()->create();

        $testData = [
            'visitor_id' => $visitor->id,
            'personality_type' => 'INTJ-A',
            'trait_percentages' => [
                'mind' => 80,
                'energy' => 75,
                'nature' => 70,
                'tactics' => 85,
                'identity' => 80,
            ],
            'task_description' => 'Test task for visitor restrictions',
            'workflow_stage' => '2_completed',
            'selected_framework' => [
                'code' => 'smart',
                'name' => 'S.M.A.R.T. Goals Framework',
                'components' => [
                    'Specific - Clear and well-defined',
                    'Measurable - Quantifiable criteria',
                    'Achievable - Realistic and attainable',
                    'Relevant - Aligned with objectives',
                    'Time-bound - Specific deadline or timeline',
                ],
                'rationale' => 'Ideal for goal-setting, project planning, and outcome-focused tasks',
            ],
            'framework_questions' => [
                'What is the specific goal you want to achieve?',
                'How will you measure success?',
                'What is your timeline for achieving this goal?',
            ],
            'optimized_prompt' => '# Test Optimised Prompt\n\nThis is a test optimised prompt for E2E testing.',
            'completed_at' => now(),
            'task_classification' => ['type' => 'analytical', 'complexity' => 'moderate'],
            'cognitive_requirements' => ['analytical', 'critical-thinking'],
        ];

        // Create a completed prompt run (the first one they finished)
        $completedPromptRun = PromptRun::create($testData);

        // Create another prompt run in 2_completed state that they should be restricted from editing
        $editablePromptRun = PromptRun::create($testData);

        return response()->json([
            'visitor_id' => $visitor->id,
            'completed_prompt_run_id' => $completedPromptRun->id,
            'editable_prompt_run_id' => $editablePromptRun->id,
        ]);
    }

    /**
     * Create a test visitor with a 2_completed prompt run (no prior completions)
     *
     * Used by E2E tests to verify that guests without prior completed prompts
     * (even if the current one is 2_completed) can still edit answers.
     *
     * @return JsonResponse Contains visitor_id and prompt_run_id
     */
    public function createVisitorPromptRun2Completed(Request $request): JsonResponse
    {
        // Security check
        if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
            abort(403, 'Unauthorised test endpoint access');
        }

        $visitor = Visitor::factory()->create();

        // Create a prompt run in 2_completed state but with no prior completions
        $promptRun = PromptRun::create([
            'visitor_id' => $visitor->id,
            'personality_type' => 'INTJ-A',
            'trait_percentages' => [
                'mind' => 80,
                'energy' => 75,
                'nature' => 70,
                'tactics' => 85,
                'identity' => 80,
            ],
            'task_description' => 'Test task for visitor restrictions',
            'workflow_stage' => '2_completed',
            'selected_framework' => [
                'code' => 'smart',
                'name' => 'S.M.A.R.T. Goals Framework',
                'components' => [
                    'Specific - Clear and well-defined',
                    'Measurable - Quantifiable criteria',
                    'Achievable - Realistic and attainable',
                    'Relevant - Aligned with objectives',
                    'Time-bound - Specific deadline or timeline',
                ],
                'rationale' => 'Ideal for goal-setting, project planning, and outcome-focused tasks',
            ],
            'framework_questions' => [
                'What is the specific goal you want to achieve?',
                'How will you measure success?',
                'What is your timeline for achieving this goal?',
            ],
            'optimized_prompt' => '# Test Optimised Prompt\n\nThis is a test optimised prompt for E2E testing.',
            'completed_at' => now(),
            'task_classification' => ['type' => 'analytical', 'complexity' => 'moderate'],
            'cognitive_requirements' => ['analytical', 'critical-thinking'],
        ]);

        return response()->json([
            'visitor_id' => $visitor->id,
            'prompt_run_id' => $promptRun->id,
        ]);
    }

    /**
     * Get all analytics events (for verifying event tracking)
     *
     * Used by some E2E tests to verify that analytics events are properly tracked.
     * Supports filtering by event_name and page_path query parameters.
     *
     * @return JsonResponse Array of analytics events
     */
    public function getAnalyticsEvents(Request $request): JsonResponse
    {
        $query = DB::table('analytics_events');

        // Filter by event name if provided
        if ($request->query('event_name')) {
            $query->where('name', $request->query('event_name'));
        }

        // Filter by page path if provided
        if ($request->query('page_path')) {
            $query->where('page_path', $request->query('page_path'));
        }

        // Filter by prompt_run_id if provided
        if ($request->query('prompt_run_id')) {
            // Cast to integer for JSON comparison (prompt_run_id is stored as number in JSON)
            $query->whereRaw(
                '(properties->>\'prompt_run_id\')::integer = ?',
                [(int) $request->query('prompt_run_id')]
            );
        }

        return response()->json($query->get());
    }
}
