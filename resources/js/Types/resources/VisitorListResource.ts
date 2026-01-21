/**
 * TypeScript definition for VisitorListResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

import type { UserResource } from '@/Types';

export interface VisitorListResource {
    readonly id: string;
    readonly user: UserResource | null;
    readonly countryCode: string;
    readonly sessionsCount: number;
    readonly createdAt: string;
}
