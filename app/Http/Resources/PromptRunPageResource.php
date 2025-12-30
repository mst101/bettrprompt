<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight resource for initial page load of prompt builder
 * Excludes large JSON fields to avoid exceeding Nginx response buffer limits
 *
 * Large fields are not needed on initial load - the frontend already knows
 * what these are or will fetch them via WebSocket updates
 *
 * @see \App\Models\PromptRun
 *
 * TypeScript interface:
 * ```typescript
 * interface PromptRunPageResource {
 *     readonly id: number;
 *     readonly userId: number | null;
 *     readonly visitorId: string | null;
 *     readonly parentId: number | null;
 *     readonly personalityType: string | null;
 *     readonly traitPercentages: {
 *         mind: number | null;
 *         energy: number | null;
 *         nature: number | null;
 *         tactics: number | null;
 *         identity: number | null;
 *     } | null;
 *     readonly taskDescription: string;
 *     readonly preAnalysisQuestions: Array<{ question: string }> | null;
 *     readonly preAnalysisAnswers: string[] | null;
 *     readonly preAnalysisReasoning: string | null;
 *     readonly preAnalysisSkipped: boolean;
 *     readonly frameworkQuestions: Array<{
 *         question: string;
 *         context?: string;
 *         cognitive_requirements?: string[];
 *     }> | null;
 *     readonly clarifyingAnswers: string[] | null;
 *     readonly currentQuestionIndex: number | null;
 *     readonly optimizedPrompt: string | null;
 *     readonly workflowStage: string;
 *     readonly errorMessage: string | null;
 *     readonly completedAt: string | null;
 *     readonly createdAt: string | null;
 *     readonly updatedAt: string | null;
 *     readonly taskClassification: {
 *         category?: string;
 *         complexity?: string;
 *         domain?: string;
 *     } | null;
 *     readonly cognitiveRequirements: string[] | null;
 *     readonly selectedFramework: {
 *         name?: string;
 *         score?: number;
 *         reasoning?: string;
 *     } | null;
 *     readonly alternativeFrameworks: Array<{
 *         name?: string;
 *         score?: number;
 *         reasoning?: string;
 *     }> | null;
 *     readonly personalityTier: string | null;
 *     readonly taskTraitAlignment: Record<string, any> | null;
 *     readonly personalityAdjustmentsPreview: Record<string, any> | null;
 *     readonly questionRationale: string | null;
 *     readonly frameworkUsed: Record<string, any> | null;
 *     readonly personalityAdjustmentsSummary: Record<string, any> | null;
 *     readonly modelRecommendations: Record<string, any> | null;
 *     readonly iterationSuggestions: string[] | null;
 *     readonly preAnalysisApiUsage: {
 *         inputTokens?: number;
 *         outputTokens?: number;
 *         totalCost?: number;
 *     } | null;
 *     readonly analysisApiUsage: {
 *         inputTokens?: number;
 *         outputTokens?: number;
 *         totalCost?: number;
 *     } | null;
 *     readonly generationApiUsage: {
 *         inputTokens?: number;
 *         outputTokens?: number;
 *         totalCost?: number;
 *     } | null;
 *     readonly user?: UserResource | null;
 *     readonly visitor?: VisitorResource | null;
 *     readonly parent?: PromptRunRelationshipResource | null;
 *     readonly children?: PromptRunRelationshipResource[];
 * }
 * ```
 */
class PromptRunPageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'visitorId' => $this->visitor_id,
            'parentId' => $this->parent_id,
            'personalityType' => $this->personality_type,
            'traitPercentages' => $this->trait_percentages,
            'taskDescription' => $this->task_description,
            'preAnalysisQuestions' => $this->pre_analysis_questions,
            'preAnalysisAnswers' => $this->pre_analysis_answers,
            // Exclude preAnalysisContext (large)
            'preAnalysisReasoning' => $this->pre_analysis_reasoning,
            'preAnalysisSkipped' => $this->pre_analysis_skipped ?? false,
            'frameworkQuestions' => $this->framework_questions,
            'clarifyingAnswers' => $this->clarifying_answers
                ? array_values($this->clarifying_answers)
                : $this->clarifying_answers,
            'currentQuestionIndex' => $this->current_question_index,
            'optimizedPrompt' => $this->optimized_prompt,
            'workflowStage' => $this->workflow_stage,
            'errorMessage' => $this->error_message,
            'completedAt' => $this->completed_at?->format('Y-m-d H:i:s'),
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Prompt Builder specific fields - exclude large ones to reduce payload
            'taskClassification' => $this->task_classification,
            'cognitiveRequirements' => $this->cognitive_requirements,
            'selectedFramework' => $this->selected_framework,
            'alternativeFrameworks' => $this->alternative_frameworks,
            'personalityTier' => $this->personality_tier,
            'taskTraitAlignment' => $this->task_trait_alignment,
            'personalityAdjustmentsPreview' => $this->personality_adjustments_preview,
            'questionRationale' => $this->question_rationale,
            'frameworkUsed' => $this->framework_used,
            'personalityAdjustmentsSummary' => $this->personality_adjustments_summary,
            'modelRecommendations' => $this->model_recommendations,
            'iterationSuggestions' => $this->iteration_suggestions,
            'preAnalysisApiUsage' => $this->pre_analysis_api_usage,
            'analysisApiUsage' => $this->analysis_api_usage,
            'generationApiUsage' => $this->generation_api_usage,

            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? new UserResource($this->user) : null;
            }),
            'visitor' => $this->whenLoaded('visitor', function () {
                return $this->visitor ? new VisitorResource($this->visitor) : null;
            }),
            // Include minimal parent/children data for UI links
            // Full details are fetched on-demand via API
            'parent' => $this->whenLoaded('parent', function () {
                return $this->parent ? new PromptRunRelationshipResource($this->parent) : null;
            }),
            'children' => $this->whenLoaded('children', function () {
                return PromptRunRelationshipResource::collection($this->children);
            }),
        ];
    }
}
