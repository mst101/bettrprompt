/**
 * TypeScript definition for AdminVisitorResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

import type { UserResource } from '@/Types';

export interface AdminVisitorResource {
    readonly id: string;
    readonly user: UserResource | null;
    readonly countryCode: string;
    readonly sessionsCount: number;
    readonly createdAt: string;
}
