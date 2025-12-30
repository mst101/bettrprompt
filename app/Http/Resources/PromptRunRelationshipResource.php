<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal resource for related prompt runs (parent/children)
 * Only includes data needed to display links, not full details.
 * Full details are fetched on-demand via API
 *
 * @see \App\Models\PromptRun
 *
 * TypeScript interface:
 * ```typescript
 * interface PromptRunRelationshipResource {
 *     readonly id: number;
 *     readonly taskDescription: string;
 *     readonly workflowStage: string;
 *     readonly createdAt: string | null;
 *     readonly personalityType: string | null;
 *     readonly selectedFramework: { name: string | null } | null;
 * }
 * ```
 */
class PromptRunRelationshipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'taskDescription' => $this->task_description,
            'workflowStage' => $this->workflow_stage,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'personalityType' => $this->personality_type,
            'selectedFramework' => $this->selected_framework ? ['name' => $this->selected_framework['name'] ?? null] : null,
        ];
    }
}
