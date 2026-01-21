<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TypeScript interface:
 * ```typescript
 * interface AmplifiedTraitResource {
 *     readonly trait: string;
 *     readonly requirementAligned: string;
 *     readonly reason: string;
 * }
 * ```
 */
class AmplifiedTraitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'trait' => $this['trait'] ?? $this->trait,
            'requirementAligned' => $this['requirement_aligned'] ?? $this->requirement_aligned,
            'reason' => $this['reason'] ?? $this->reason,
        ];
    }
}
