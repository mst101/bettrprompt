<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TypeScript interface:
 * ```typescript
 * interface ModelRecommendationResource {
 *     readonly rank: number;
 *     readonly model: string;
 *     readonly modelId: string;
 *     readonly rationale: string;
 * }
 * ```
 */
class ModelRecommendationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this['rank'] ?? $this->rank,
            'model' => $this['model'] ?? $this->model,
            'modelId' => $this['model_id'] ?? $this->model_id,
            'rationale' => $this['rationale'] ?? $this->rationale,
        ];
    }
}
