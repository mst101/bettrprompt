<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateChildFromAnswersRequest;
use App\Http\Requests\CreateChildFromTaskRequest;
use App\Jobs\ProcessPreAnalysis;
use App\Jobs\ProcessPromptGeneration;
use App\Models\PromptRun;
use App\Models\Visitor;
use App\Services\DatabaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API Controller for handling prompt run edit operations
 *
 * Provides JSON endpoints for editing prompts. Enforces visitor restrictions:
 * - Authenticated users can always edit
 * - Guests (visitors) can only edit if they haven't completed a prompt yet
 *
 * Returns JSON responses with appropriate HTTP status codes:
 * - 403 Forbidden when visitor restrictions are violated
 */
class PromptRunEditController extends Controller
{
    /**
     * Create a child prompt run from edited task description
     *
     * POST /api/prompt-runs/{promptRun}/create-child-from-task
     */
    public function createChildFromTask(
        CreateChildFromTaskRequest $request,
        PromptRun $promptRun
    ): JsonResponse {
        // Check visitor restrictions
        $visitorRestrictionError = $this->checkVisitorRestriction($request, $promptRun);
        if ($visitorRestrictionError) {
            return $visitorRestrictionError;
        }

        $validated = $request->validated();

        try {
            $user = auth()->user();
            $visitorId = $promptRun->visitor_id ?? $this->getVisitorIdFromRequest($request);
            $personalityType = $user?->personality_type ?? $promptRun->personality_type;
            $traitPercentages = $user?->trait_percentages ?? $promptRun->trait_percentages;

            // Create child prompt run
            $childPromptRun = DatabaseService::retryOnDeadlock(function () use (
                $user,
                $visitorId,
                $promptRun,
                $validated,
                $personalityType,
                $traitPercentages
            ) {
                return PromptRun::create([
                    'visitor_id' => $visitorId,
                    'user_id' => $user?->id,
                    'parent_id' => $promptRun->id,
                    'personality_type' => $personalityType,
                    'trait_percentages' => $traitPercentages,
                    'task_description' => $validated['task_description'],
                    'workflow_stage' => '0_processing',
                ]);
            });

            // Dispatch pre-analysis job
            ProcessPreAnalysis::dispatch($childPromptRun, 'pgsql');

            return response()->json([
                'success' => true,
                'prompt_run_id' => $childPromptRun->id,
            ], 201);
        } catch (Exception $e) {
            Log::error('API: Failed to create child prompt run from task', [
                'parent_prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => __('messages.prompt_builder.create_prompt_run_failed'),
            ], 500);
        }
    }

    /**
     * Create a child prompt run from edited clarifying answers
     *
     * POST /api/prompt-runs/{promptRun}/create-child-from-answers
     */
    public function createChildFromAnswers(
        CreateChildFromAnswersRequest $request,
        PromptRun $promptRun
    ): JsonResponse {
        // Check visitor restrictions
        $visitorRestrictionError = $this->checkVisitorRestriction($request, $promptRun);
        if ($visitorRestrictionError) {
            return $visitorRestrictionError;
        }

        if (empty($promptRun->framework_questions)) {
            return response()->json([
                'error' => __('messages.prompt_builder.no_clarifying_questions'),
            ], 422);
        }

        $validated = $request->validated();

        try {
            $user = auth()->user();
            $visitorId = $promptRun->visitor_id ?? $this->getVisitorIdFromRequest($request);

            // Normalize answers (convert empty strings to null)
            $clarifyingAnswers = array_values(
                array_map(
                    fn ($answer) => ($answer === '' || $answer === null) ? null : $answer,
                    $validated['clarifying_answers'],
                )
            );

            // Create child prompt run
            $childPromptRun = DatabaseService::retryOnDeadlock(function () use (
                $user,
                $visitorId,
                $promptRun,
                $clarifyingAnswers
            ) {
                return PromptRun::create([
                    'visitor_id' => $visitorId,
                    'user_id' => $user?->id,
                    'parent_id' => $promptRun->id,
                    'personality_type' => $user?->personality_type ?? $promptRun->personality_type,
                    'trait_percentages' => $user?->trait_percentages ?? $promptRun->trait_percentages,
                    'task_description' => $promptRun->task_description,
                    'pre_analysis_questions' => $promptRun->pre_analysis_questions,
                    'pre_analysis_answers' => $promptRun->pre_analysis_answers,
                    'pre_analysis_context' => $promptRun->pre_analysis_context,
                    'pre_analysis_reasoning' => $promptRun->pre_analysis_reasoning,
                    'task_classification' => $promptRun->task_classification,
                    'cognitive_requirements' => $promptRun->cognitive_requirements,
                    'selected_framework' => $promptRun->selected_framework,
                    'alternative_frameworks' => $promptRun->alternative_frameworks,
                    'personality_tier' => $promptRun->personality_tier,
                    'task_trait_alignment' => $promptRun->task_trait_alignment,
                    'personality_adjustments_preview' => $promptRun->personality_adjustments_preview,
                    'question_rationale' => $promptRun->question_rationale,
                    'framework_questions' => $promptRun->framework_questions,
                    'clarifying_answers' => $clarifyingAnswers,
                    'workflow_stage' => '2_processing',
                ]);
            });

            // Dispatch prompt generation job
            ProcessPromptGeneration::dispatch($childPromptRun, 'pgsql');

            return response()->json([
                'success' => true,
                'prompt_run_id' => $childPromptRun->id,
            ], 201);
        } catch (Exception $e) {
            Log::error('API: Failed to create child prompt run from answers', [
                'parent_prompt_run_id' => $promptRun->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => __('messages.prompt_builder.create_prompt_run_failed'),
            ], 500);
        }
    }

    /**
     * Check if a visitor is restricted from editing
     *
     * @return JsonResponse|null Returns error response if restricted, null otherwise
     */
    private function checkVisitorRestriction(Request $request, PromptRun $promptRun): ?JsonResponse
    {
        // Authenticated users can always edit
        if (auth()->check()) {
            return null;
        }

        // Guest visitor - check if they have completed a prompt
        // Use the prompt run's visitor_id, not the cookie
        $visitorId = $promptRun->visitor_id;

        Log::info('API: Checking visitor restriction', [
            'prompt_run_id' => $promptRun->id,
            'visitor_id' => $visitorId,
            'auth_check' => auth()->check(),
        ]);

        if ($visitorId) {
            $visitor = Visitor::find($visitorId);

            Log::info('API: Found visitor', [
                'visitor_id' => $visitorId,
                'visitor' => $visitor?->id,
            ]);

            if ($visitor) {
                $hasCompleted = $visitor->hasCompletedPrompts();

                Log::info('API: Checking completed prompts', [
                    'visitor_id' => $visitorId,
                    'has_completed' => $hasCompleted,
                ]);

                if ($hasCompleted) {
                    return response()->json([
                        'error' => __('messages.prompt_builder.visitor_limit_reached'),
                    ], 403);
                }
            }
        }

        return null;
    }

    /**
     * Edit answers on the current prompt run (fallback endpoint)
     *
     * POST /api/prompt-runs/{promptRun}/edit-answers
     *
     * This is a fallback endpoint used to test that the backend prevents editing
     * even if someone bypasses the UI modal. It delegates to createChildFromAnswers.
     */
    public function editAnswers(
        CreateChildFromAnswersRequest $request,
        PromptRun $promptRun
    ): JsonResponse {
        return $this->createChildFromAnswers($request, $promptRun);
    }

    /**
     * Get visitor ID from request cookie
     */
    private function getVisitorIdFromRequest(Request $request): ?int
    {
        $visitorId = $request->cookie('visitor_id');

        return $visitorId ? (int) $visitorId : null;
    }
}
