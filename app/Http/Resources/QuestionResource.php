<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'questionText' => $this->question_text,
            'purpose' => $this->purpose,
            'priority' => $this->priority,
            'taskCategoryCode' => $this->task_category_code,
            'frameworkCode' => $this->framework_code,
            'isUniversal' => (bool) $this->is_universal,
            'isConditional' => (bool) $this->is_conditional,
            'conditionText' => $this->condition_text,
            'displayOrder' => (int) $this->display_order,
            'isActive' => (bool) $this->is_active,
            'variantsCount' => $this->variants_count ?? $this->variants->count(),
            'variants' => QuestionVariantResource::collection($this->whenLoaded('variants')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
