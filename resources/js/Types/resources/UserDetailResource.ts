/**
 * TypeScript definition for UserDetailResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

import type { VisitorDetailResource } from '@/Types';

export interface UserDetailResource {
    readonly id: number;
    readonly name: string;
    readonly email: string;
    readonly personalityType: string | null;
    readonly isAdmin: boolean;
    readonly createdAt: string;
    readonly visitor?: VisitorDetailResource | null;
}
