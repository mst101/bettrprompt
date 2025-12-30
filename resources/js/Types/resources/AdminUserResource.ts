/**
 * TypeScript definition for AdminUserResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

export interface AdminUserResource {
    readonly id: number;
    readonly name: string;
    readonly email: string;
    readonly personalityType: string | null;
    readonly isAdmin: boolean;
    readonly createdAt: string;
    readonly visitorsCount: number;
    readonly promptRunsCount: number;
}
