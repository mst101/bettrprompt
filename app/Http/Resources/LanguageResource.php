<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @see \App\Models\Language
 *
 * TypeScript interface:
 * ```typescript
 * interface Language {
 *   readonly id: string;
 *   readonly name: string;
 *   readonly active: boolean;
 *   readonly createdAt: string;
 *   readonly updatedAt: string;
 * }
 * ```
 */
class LanguageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'active' => $this->active,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
