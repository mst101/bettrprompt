<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @see \App\Models\Visitor
 *
 * TypeScript interface:
 * ```typescript
 * interface Visitor {
 *   readonly id: number;
 *   readonly userId: number | null;
 *   readonly utmSource: string | null;
 *   readonly utmMedium: string | null;
 *   readonly utmCampaign: string | null;
 *   readonly utmTerm: string | null;
 *   readonly utmContent: string | null;
 *   readonly referrer: string | null;
 *   readonly landingPage: string | null;
 *   readonly userAgent: string | null;
 *   readonly ipAddress: string | null;
 *   readonly firstVisitAt: string;
 *   readonly lastVisitAt: string;
 *   readonly visitCount: number;
 *   readonly convertedAt: string | null;
 *   readonly createdAt: string;
 *   readonly updatedAt: string;
 *
 *   // Relationships
 *   readonly user?: UserResource | null;
 *   readonly promptRuns?: readonly PromptRunResource[];
 * }
 * ```
 * The TypeScript interface is generated based on the attributes and relationships defined in this resource.
 * It is intended to be used with Vue.js Composition API and TypeScript.
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
            'userId' => $this->user_id,
            'utmSource' => $this->utm_source,
            'utmMedium' => $this->utm_medium,
            'utmCampaign' => $this->utm_campaign,
            'utmTerm' => $this->utm_term,
            'utmContent' => $this->utm_content,
            'referrer' => $this->referrer,
            'landingPage' => $this->landing_page,
            'userAgent' => $this->user_agent,
            'ipAddress' => $this->ip_address,
            'firstVisitAt' => $this->first_visit_at?->format('Y-m-d H:i:s'),
            'lastVisitAt' => $this->last_visit_at?->format('Y-m-d H:i:s'),
            'visitCount' => $this->visit_count,
            'convertedAt' => $this->converted_at?->format('Y-m-d H:i:s'),
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? new UserResource($this->user) : null;
            }),
            'promptRuns' => $this->whenLoaded('promptRuns', function () {
                return $this->promptRuns ? PromptRunResource::collection($this->promptRuns) : [];
            }, []),
        ];
    }
}
