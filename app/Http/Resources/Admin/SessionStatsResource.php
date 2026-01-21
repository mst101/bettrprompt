<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TypeScript interface:
 * ```typescript
 * interface AdminSessionStatsResource {
 *     readonly totalSessions: number;
 *     readonly totalPageViews: number;
 *     readonly avgDuration: number;
 *     readonly bounceRate: number;
 *     readonly converted: number;
 *     readonly lastActive?: string | null;
 * }
 * ```
 */
class SessionStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'totalSessions' => $this['total_sessions'] ?? 0,
            'totalPageViews' => $this['total_page_views'] ?? 0,
            'avgDuration' => $this['avg_duration'] ?? 0,
            'bounceRate' => $this['bounce_rate'] ?? 0,
            'converted' => $this['converted'] ?? 0,
            'lastActive' => $this['last_active'] ?? null,
        ];
    }
}
