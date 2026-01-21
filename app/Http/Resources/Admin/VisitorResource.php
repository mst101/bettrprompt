<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for admin visitor list
 *
 * @see \App\Models\Visitor
 *
 * TypeScript interface:
 * ```typescript
 * interface AdminVisitor {
 *     readonly id: string;
 *     readonly user: UserResource | null;
 *     readonly countryCode: string;
 *     readonly sessionsCount: number;
 *     readonly createdAt: string;
 * }
 * ```
 */
class VisitorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'countryCode' => $this->country_code,
            'sessionsCount' => $this->sessions_count,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),

            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? (new UserResource($this->user))->resolve() : null;
            }),
        ];
    }
}
