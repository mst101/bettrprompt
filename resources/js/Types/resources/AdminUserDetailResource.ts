/**
 * TypeScript definition for AdminUserDetailResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

import type { AdminVisitorDetailResource } from '@/Types';

export interface AdminUserDetailResource {
    readonly id: number;
    readonly name: string;
    readonly email: string;
    readonly personalityType: string | null;
    readonly isAdmin: boolean;
    readonly createdAt: string;
    readonly visitor?: AdminVisitorDetailResource | null;
}
