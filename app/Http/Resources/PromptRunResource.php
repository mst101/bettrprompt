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
 *   readonly traitPercentages: Array<unknown> | null;
 *   readonly taskDescription: string;
 *   readonly selectedFramework: string | null;
 *   readonly frameworkReasoning: string | null;
 *   readonly personalityApproach: string | null;
 *   readonly frameworkQuestions: Array<unknown> | null;
 *   readonly clarifyingAnswers: Array<unknown> | null;
 *   readonly optimizedPrompt: string | null;
 *   readonly n8nRequestPayload: Array<unknown> | null;
 *   readonly n8nResponsePayload: string | null;
 *   readonly status: string;
 *   readonly workflowStage: string;
 *   readonly errorMessage: string | null;
 *   readonly completedAt: string | null;
 *   readonly createdAt: string;
 *   readonly updatedAt: string;
 *
 *   // Relationships
 *   readonly user?: UserResource | null;
 *   readonly visitor?: VisitorResource | null;
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
            'selectedFramework' => $this->selected_framework,
            'frameworkReasoning' => $this->framework_reasoning,
            'personalityApproach' => $this->personality_approach,
            'frameworkQuestions' => $this->framework_questions,
            'clarifyingAnswers' => $this->clarifying_answers,
            'optimizedPrompt' => $this->optimized_prompt,
            'n8nRequestPayload' => $this->n8n_request_payload,
            'n8nResponsePayload' => $this->n8n_response_payload,
            'status' => $this->status,
            'workflowStage' => $this->workflow_stage,
            'errorMessage' => $this->error_message,
            'completedAt' => $this->completed_at?->format('Y-m-d H:i:s'),
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? new UserResource($this->user) : null;
            }),
            'visitor' => $this->whenLoaded('visitor', function () {
                return $this->visitor ? new VisitorResource($this->visitor) : null;
            }),
            'parent' => $this->whenLoaded('parent', function () {
                return $this->parent ? new PromptRunResource($this->parent) : null;
            }),
            'children' => $this->whenLoaded('children', function () {
                return $this->children ? PromptRunResource::collection($this->children) : [];
            }, []),
        ];
    }
}
