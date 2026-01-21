<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TypeScript interface:
 * ```typescript
 * interface UserDetailResource {
 *     readonly id: number;
 *     readonly name: string;
 *     readonly email: string;
 *     readonly personalityType: string | null;
 *     readonly isAdmin: boolean;
 *     readonly createdAt: string;
 *     readonly visitor?: VisitorDetailResource | null;
 * }
 * ```
 */
class UserDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'personalityType' => $this->personality_type,
            'isAdmin' => $this->is_admin ?? false,
            'createdAt' => $this->created_at?->toIso8601String(),

            // Relationships
            'visitor' => $this->whenLoaded('visitor', function () {
                return $this->visitor ? VisitorDetailResource::make($this->visitor)->resolve() : null;
            }),
        ];
    }
}
