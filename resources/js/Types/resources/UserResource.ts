/**
 * TypeScript definition for UserResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

export interface UserResource {
    readonly id: number;
    readonly name: string;
    readonly email: string;
    readonly createdAt: string;
    readonly updatedAt: string;
    readonly emailVerifiedAt: string | null;
    readonly personalityType: string | null;
    readonly traitPercentages: {
        mind: number | null;
        energy: number | null;
        nature: number | null;
        tactics: number | null;
        identity: number | null;
    } | null;
    readonly isAdmin: boolean;
    readonly subscription: {
        readonly tier: string;
        readonly isPaid: boolean;
        readonly isPro: boolean;
        readonly isPrivate: boolean;
        readonly isFree: boolean;
        readonly promptsUsed: number;
        readonly promptsRemaining: number;
        readonly promptLimit: number;
        readonly daysUntilReset: number;
        readonly subscriptionEndsAt: string | null;
        readonly onGracePeriod: boolean;
    };
    readonly visitor?: {
        readonly id: string;
        readonly sessions?: unknown[];
    };
}
