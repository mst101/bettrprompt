<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @see \App\Models\PromptRun
 *
 * TypeScript interface:
 * ```typescript
 * interface PromptRun {
 *   readonly id: number;
 *   readonly userId: number | null;
 *   readonly visitorId: string;
 *   readonly parentId: number | null;
 *   readonly personalityType: string | null;
 *   readonly traitPercentages: Record<string, number> | null;
 *   readonly taskDescription: string;
 *   readonly frameworkQuestions: Array<unknown> | null;
 *   readonly clarifyingAnswers: Array<unknown> | null;
 *   readonly optimizedPrompt: string | null;
 *   readonly status: string;
 *   readonly workflowStage: string;
 *   readonly errorMessage: string | null;
 *   readonly completedAt: string | null;
 *   readonly createdAt: string;
 *   readonly updatedAt: string;
 *
 *   // Prompt Builder specific fields
 *   readonly taskClassification: Record<string, unknown> | null;
 *   readonly cognitiveRequirements: Record<string, unknown> | null;
 *   readonly selectedFramework: Record<string, unknown> | null;
 *   readonly alternativeFrameworks: Array<unknown> | null;
 *   readonly personalityTier: string | null;
 *   readonly taskTraitAlignment: Record<string, unknown> | null;
 *   readonly personalityAdjustmentsPreview: Array<string> | null;
 *   readonly questionRationale: string | null;
 *   readonly frameworkUsed: Record<string, unknown> | null;
 *   readonly personalityAdjustmentsSummary: Array<string> | null;
 *   readonly modelRecommendations: Array<unknown> | null;
 *   readonly iterationSuggestions: Array<string> | null;
 *   readonly analysisApiUsage: Record<string, unknown> | null;
 *   readonly generationApiUsage: Record<string, unknown> | null;
 *
 *   // Relationships
 *   readonly visitor?: VisitorResource;
 *   readonly user?: UserResource | null;
 *   readonly parent?: PromptRunResource | null;
 *   readonly children?: readonly PromptRunResource[];
 * }
 * ```
 * The TypeScript interface is generated based on the attributes and relationships defined in this resource.
 * It is intended to be used with Vue.js Composition API and TypeScript.
 */
class PromptRunResource extends JsonResource
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
            'frameworkQuestions' => $this->framework_questions,
            'clarifyingAnswers' => $this->clarifying_answers,
            'optimizedPrompt' => $this->optimized_prompt,
            'status' => $this->status,
            'workflowStage' => $this->workflow_stage,
            'errorMessage' => $this->error_message,
            'completedAt' => $this->completed_at?->format('Y-m-d H:i:s'),
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Prompt Builder specific fields
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
            'analysisApiUsage' => $this->analysis_api_usage,
            'generationApiUsage' => $this->generation_api_usage,

            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? new UserResource($this->user) : null;
            }),
            'visitor' => $this->whenLoaded('visitor', function () {
                return $this->visitor ? new VisitorResource($this->visitor) : null;
            }),
            'parent' => $this->whenLoaded('parent', function () {
                return $this->parent ? (new PromptRunResource($this->parent))->resolve() : null;
            }),
            'children' => $this->whenLoaded('children', function () {
                return $this->children->map(fn ($child) => (new PromptRunResource($child))->resolve())->values()->all();
            }),
        ];
    }
}
