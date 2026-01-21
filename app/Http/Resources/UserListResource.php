<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for user list view
 *
 * @see \App\Models\User
 *
 * TypeScript interface:
 * ```typescript
 * interface UserListResource {
 *     readonly id: number;
 *     readonly name: string;
 *     readonly email: string;
 *     readonly personalityType: string | null;
 *     readonly isAdmin: boolean;
 *     readonly createdAt: string;
 *     readonly visitorsCount: number;
 *     readonly promptRunsCount: number;
 * }
 * ```
 */
class UserListResource extends JsonResource
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
            'personalityType' => $this->personality_type,
            'isAdmin' => $this->is_admin ?? false,
            'createdAt' => $this->created_at?->toIso8601String(),
            'visitorsCount' => $this->visitors_count ?? 0,
            'promptRunsCount' => $this->prompt_runs_count ?? 0,
        ];
    }
}
