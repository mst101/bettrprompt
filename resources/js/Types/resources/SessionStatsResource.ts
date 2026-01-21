/**
 * TypeScript definition for SessionStatsResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

export interface SessionStatsResource {
    readonly totalSessions: number;
    readonly totalPageViews: number;
    readonly avgDuration: number;
    readonly bounceRate: number;
    readonly converted: number;
    readonly lastActive?: string | null;
}
