/**
 * TypeScript definition for VisitorResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

import type { UserResource } from '@/Types';

export interface VisitorResource {
    readonly id: string;
    readonly userId: number | null;
    readonly utmSource: string | null;
    readonly utmMedium: string | null;
    readonly utmCampaign: string | null;
    readonly utmTerm: string | null;
    readonly utmContent: string | null;
    readonly referrer: string | null;
    readonly landingPage: string | null;
    readonly userAgent: string | null;
    readonly ipAddress: string | null;
    readonly firstVisitAt: string;
    readonly lastVisitAt: string;
    readonly convertedAt: string | null;
    readonly createdAt: string;
    readonly updatedAt: string;
    // Relationships
    readonly user?: UserResource | null;
    readonly promptRuns?: readonly PromptRunResource[];
}
