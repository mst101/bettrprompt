/**
 * Base PromptRun Eloquent model shape
 *
 * ⚠️  This represents the raw database model with loose typing.
 * In most cases, you should use PromptRunResource instead.
 *
 * Use this type only for:
 * - Raw Eloquent model instances
 * - Relationship type hints in other models
 *
 * @see PromptRunResource for component props (recommended)
 */

import type { User, Visitor } from '@/Types';

export interface PromptRun {
    readonly id: number;
    readonly createdAt: string;
    readonly updatedAt: string;
    readonly userId: number | null;
    readonly visitorId: string | null;
    readonly parentId: number | null;
    readonly personalityType: string | null;
    readonly traitPercentages: Record<string, unknown>;
    readonly taskDescription: string | null;
    readonly selectedFramework: string | null;
    readonly frameworkReasoning: string | null;
    readonly personalityApproach: string | null;
    readonly frameworkQuestions: Record<string, unknown>;
    readonly clarifyingAnswers: Record<string, unknown>;
    readonly optimizedPrompt: string | null;
    readonly n8nRequestPayload: Record<string, unknown>;
    readonly n8nResponsePayload: unknown;
    readonly status: string | null;
    readonly workflowStage: string | null;
    readonly errorMessage: string | null;
    readonly completedAt: string;
    readonly visitor: Visitor | null;
    readonly user: User | null;
    readonly parent: PromptRun | null;
    readonly children: PromptRun[] | null;
}
