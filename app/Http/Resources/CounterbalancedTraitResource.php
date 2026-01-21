<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TypeScript interface:
 * ```typescript
 * interface CounterbalancedTraitResource {
 *     readonly trait: string;
 *     readonly requirementOpposed: string;
 *     readonly reason: string;
 *     readonly injection: string;
 * }
 * ```
 */
class CounterbalancedTraitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'trait' => $this['trait'] ?? $this->trait,
            'requirementOpposed' => $this['requirement_opposed'] ?? $this->requirement_opposed,
            'reason' => $this['reason'] ?? $this->reason,
            'injection' => $this['injection'] ?? $this->injection,
        ];
    }
}
