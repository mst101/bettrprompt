<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TypeScript interface:
 * ```typescript
 * interface NeutralTraitResource {
 *     readonly trait: string;
 *     readonly reason: string;
 * }
 * ```
 */
class NeutralTraitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'trait' => $this['trait'] ?? $this->trait,
            'reason' => $this['reason'] ?? $this->reason,
        ];
    }
}
