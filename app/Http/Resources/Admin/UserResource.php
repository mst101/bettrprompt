<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for admin user list
 *
 * @see \App\Models\User
 *
 * TypeScript interface:
 * ```typescript
 * interface AdminUser {
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
            'personalityType' => $this->personality_type,
            'isAdmin' => $this->is_admin ?? false,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'visitorsCount' => $this->visitors_count ?? 0,
            'promptRunsCount' => $this->prompt_runs_count ?? 0,
        ];
    }
}
