<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TypeScript interface:
 * ```typescript
 * interface AdminEventResource {
 *     readonly eventId: string;
 *     readonly name: string;
 *     readonly pagePath: string | null;
 *     readonly occurredAt: string;
 *     readonly properties: Record<string, unknown>;
 * }
 * ```
 */
class AdminEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'eventId' => $this->event_id,
            'name' => $this->name,
            'pagePath' => $this->page_path,
            'occurredAt' => $this->occurred_at?->format('Y-m-d H:i:s'),
            'properties' => $this->properties ?? [],
        ];
    }
}
