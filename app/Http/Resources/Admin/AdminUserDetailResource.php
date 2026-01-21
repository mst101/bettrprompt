<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TypeScript interface:
 * ```typescript
 * interface AdminUserDetailResource {
 *     readonly id: number;
 *     readonly name: string;
 *     readonly email: string;
 *     readonly personalityType: string | null;
 *     readonly isAdmin: boolean;
 *     readonly createdAt: string;
 *     readonly visitor?: AdminVisitorDetailResource | null;
 * }
 * ```
 */
class AdminUserDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'personalityType' => $this->personality_type,
            'isAdmin' => $this->is_admin ?? false,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),

            // Relationships
            'visitor' => $this->whenLoaded('visitor', function () {
                return $this->visitor ? AdminVisitorDetailResource::make($this->visitor)->only(['id', 'sessions']) : null;
            }),
        ];
    }
}
