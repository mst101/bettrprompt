/**
 * TypeScript definition for WorkflowStageResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

export interface WorkflowStageResource {
    readonly stage: number;
    readonly totalExecutions: number;
    readonly successful: number;
    readonly failed: number;
    readonly successRate: number;
    readonly avgDurationMs: number | null;
    readonly avgCostUsd: number | null;
}
