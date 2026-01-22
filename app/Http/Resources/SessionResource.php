<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TypeScript interface:
 * ```typescript
 * interface SessionResource {
 *     readonly id: string;
 *     readonly startedAt: string;
 *     readonly endedAt: string | null;
 *     readonly durationSeconds: number;
 *     readonly pageCount: number;
 *     readonly entryPage: string;
 *     readonly exitPage: string | null;
 *     readonly deviceType: string;
 *     readonly utmSource: string | null;
 *     readonly utmMedium: string | null;
 *     readonly utmCampaign: string | null;
 *     readonly isBounce: boolean;
 *     readonly converted: boolean;
 *     readonly events?: EventResource[];
 * }
 * ```
 */
class SessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'startedAt' => $this->started_at?->toIso8601String(),
            'endedAt' => $this->ended_at?->toIso8601String(),
            'durationSeconds' => $this->duration_seconds,
            'pageCount' => $this->whenLoaded('events', fn () => $this->events->where('name', 'page_view')->count(), 0),
            'entryPage' => $this->entry_page,
            'exitPage' => $this->exit_page,
            'deviceType' => $this->device_type,
            'utmSource' => $this->utm_source,
            'utmMedium' => $this->utm_medium,
            'utmCampaign' => $this->utm_campaign,
            'isBounce' => $this->isBounce(),
            'converted' => $this->isConverted(),

            // Relationships
            'events' => $this->whenLoaded('events', function () {
                return EventResource::collection($this->events)->resolve();
            }),
        ];
    }
}
