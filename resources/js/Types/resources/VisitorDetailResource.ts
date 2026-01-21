/**
 * TypeScript definition for VisitorDetailResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

import type { AdminSessionResource, UserResource } from '@/Types';

export interface VisitorDetailResource {
    readonly id: string;
    readonly countryCode: string;
    readonly createdAt: string;
    readonly user: UserResource | null;
    readonly sessions: AdminSessionResource[];
}
