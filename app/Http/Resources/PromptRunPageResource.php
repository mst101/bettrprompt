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

            // Relationships - don't include parent/children to avoid oversized payloads
            // They can be fetched separately if needed
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? new UserResource($this->user) : null;
            }),
            'visitor' => $this->whenLoaded('visitor', function () {
                return $this->visitor ? new VisitorResource($this->visitor) : null;
            }),
        ];
    }
}
