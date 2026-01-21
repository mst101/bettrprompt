/**
 * TypeScript definition for SessionResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

import type { AdminEventResource } from '@/Types';

export interface SessionResource {
    readonly id: string;
    readonly startedAt: string;
    readonly endedAt: string | null;
    readonly durationSeconds: number;
    readonly pageCount: number;
    readonly entryPage: string;
    readonly exitPage: string | null;
    readonly deviceType: string;
    readonly utmSource: string | null;
    readonly utmMedium: string | null;
    readonly utmCampaign: string | null;
    readonly isBounce: boolean;
    readonly converted: boolean;
    readonly events?: AdminEventResource[];
}
