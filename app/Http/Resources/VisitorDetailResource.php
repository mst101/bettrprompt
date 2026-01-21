<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TypeScript interface:
 * ```typescript
 * interface VisitorDetailResource {
 *     readonly id: string;
 *     readonly countryCode: string;
 *     readonly createdAt: string;
 *     readonly user: UserResource | null;
 *     readonly sessions: SessionResource[];
 * }
 * ```
 */
class VisitorDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'countryCode' => $this->country_code,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),

            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? (new UserResource($this->user))->resolve() : null;
            }),
            'sessions' => $this->whenLoaded('sessions', function () {
                return SessionResource::collection($this->sessions);
            }),
        ];
    }
}
