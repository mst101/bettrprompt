/**
 * TypeScript definition for UserListResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

export interface UserListResource {
    readonly id: number;
    readonly name: string;
    readonly email: string;
    readonly personalityType: string | null;
    readonly isAdmin: boolean;
    readonly createdAt: string;
    readonly visitorsCount: number;
    readonly promptRunsCount: number;
}
