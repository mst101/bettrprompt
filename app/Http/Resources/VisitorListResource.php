<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for visitor list view
 *
 * @see \App\Models\Visitor
 *
 * TypeScript interface:
 * ```typescript
 * interface VisitorListResource {
 *     readonly id: string;
 *     readonly user: UserResource | null;
 *     readonly countryCode: string;
 *     readonly sessionsCount: number;
 *     readonly createdAt: string;
 *     readonly lastSeenAt: string;
 * }
 * ```
 */
class VisitorListResource extends JsonResource
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
            'createdAt' => $this->created_at?->toIso8601String(),
            'lastSeenAt' => $this->last_visit_at?->toIso8601String(),

            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? (new UserResource($this->user))->resolve() : null;
            }),
        ];
    }
}
