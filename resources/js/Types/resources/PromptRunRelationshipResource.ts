/**
 * TypeScript definition for PromptRunRelationshipResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

export interface PromptRunRelationshipResource {
    readonly id: number;
    readonly taskDescription: string;
    readonly workflowStage: string;
    readonly createdAt: string | null;
    readonly personalityType: string | null;
    readonly selectedFramework: { name: string | null } | null;
}
