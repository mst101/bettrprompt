<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal resource for related prompt runs (parent/children)
 * Only includes data needed to display links, not full details
 * Full details are fetched on-demand via API
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
        ];
    }
}
