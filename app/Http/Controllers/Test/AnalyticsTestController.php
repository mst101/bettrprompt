<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use App\Models\PromptRun;
use App\Models\QuestionAnalytic;
use App\Models\Visitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    public function createVisitorPromptRun(): JsonResponse
    {
        $visitor = Visitor::factory()->create();

        $promptRun = PromptRun::factory()
            ->for($visitor)
            ->create(['workflow_stage' => '1_completed']);

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
    public function createVisitorWithCompletedPrompt(): JsonResponse
    {
        $visitor = Visitor::factory()->create();

        // Create a completed prompt run (2_completed stage)
        $promptRun = PromptRun::factory()
            ->for($visitor)
            ->create(['workflow_stage' => '2_completed']);

        return response()->json([
            'visitor_id' => $visitor->id,
            'prompt_run_id' => $promptRun->id,
        ]);
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
    public function createVisitorWithCompletedPromptForEdit(): JsonResponse
    {
        $visitor = Visitor::factory()->create();

        // Create a completed prompt run (the first one they finished)
        $completedPromptRun = PromptRun::factory()
            ->for($visitor)
            ->create(['workflow_stage' => '2_completed']);

        // Create another prompt run in 2_completed state that they should be restricted from editing
        $editablePromptRun = PromptRun::factory()
            ->for($visitor)
            ->create(['workflow_stage' => '2_completed']);

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
    public function createVisitorPromptRun2Completed(): JsonResponse
    {
        $visitor = Visitor::factory()->create();

        // Create a prompt run in 2_completed state but with no prior completions
        $promptRun = PromptRun::factory()
            ->for($visitor)
            ->create(['workflow_stage' => '2_completed']);

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
            $query->where('properties->prompt_run_id', '=', $request->query('prompt_run_id'));
        }

        return response()->json($query->get());
    }
}
