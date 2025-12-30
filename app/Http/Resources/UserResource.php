<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @see \App\Models\User
 *
 * TypeScript interface:
 * ```typescript
 * interface User {
 *     readonly id: number;
 *     readonly name: string;
 *     readonly email: string;
 *     readonly createdAt: string;
 *     readonly updatedAt: string;
 *     readonly emailVerifiedAt: string | null;
 *     readonly personalityType: string | null;
 *     readonly traitPercentages: {
 *         mind: number | null;
 *         energy: number | null;
 *         nature: number | null;
 *         tactics: number | null;
 *         identity: number | null;
 *     } | null;
 *     readonly isAdmin: boolean;
 * }
 * ```
 * The TypeScript interface is generated based on the attributes and relationships defined in this resource.
 * It is intended to be used with Vue.js Composition API and TypeScript.
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
            'emailVerifiedAt' => $this->email_verified_at?->format('Y-m-d H:i:s'),
            'personalityType' => $this->personality_type,
            'traitPercentages' => $this->trait_percentages,
            'isAdmin' => $this->is_admin ?? false,
        ];
    }
}
