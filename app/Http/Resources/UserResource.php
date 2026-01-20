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
 *     readonly subscription: {
 *         readonly tier: string;
 *         readonly isPaid: boolean;
 *         readonly isPro: boolean;
 *         readonly isPrivate: boolean;
 *         readonly isFree: boolean;
 *         readonly promptsUsed: number;
 *         readonly promptsRemaining: number;
 *         readonly promptLimit: number;
 *         readonly daysUntilReset: number;
 *         readonly subscriptionEndsAt: string | null;
 *         readonly onGracePeriod: boolean;
 *     };
 *     readonly visitor?: {
 *         readonly id: string;
 *         readonly sessions?: unknown[];
 *     };
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
            'subscription' => $this->getSubscriptionStatus(),
            'visitor' => $this->whenLoaded('visitor', function () {
                return [
                    'id' => $this->visitor->id,
                    'sessions' => $this->visitor->relationLoaded('sessions')
                        ? $this->visitor->sessions->map(fn ($session) => [
                            'id' => $session->id,
                            'started_at' => $session->started_at?->toIso8601String(),
                            'ended_at' => $session->ended_at?->toIso8601String(),
                            'duration_seconds' => $session->duration_seconds,
                            'page_count' => $session->page_count,
                            'entry_page' => $session->entry_page,
                            'exit_page' => $session->exit_page,
                            'device_type' => $session->device_type,
                            'utm_source' => $session->utm_source,
                            'utm_medium' => $session->utm_medium,
                            'utm_campaign' => $session->utm_campaign,
                            'is_bounce' => $session->is_bounce,
                            'converted' => $session->converted,
                            'events' => $session->relationLoaded('events')
                                ? $session->events->map(fn ($event) => [
                                    'event_id' => $event->id,
                                    'name' => $event->name,
                                    'page_path' => $event->page_path,
                                    'occurred_at' => $event->occurred_at?->toIso8601String(),
                                    'properties' => $event->properties,
                                ])->toArray()
                                : null,
                        ])->toArray()
                        : null,
                ];
            }),
        ];
    }
}
