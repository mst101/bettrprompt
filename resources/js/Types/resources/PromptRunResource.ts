/**
 * TypeScript definition for PromptRunResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

import type { UserResource, VisitorResource } from '@/Types';

export interface PromptRunResource {
    readonly id: number;
    readonly userId: number | null;
    readonly visitorId: string;
    readonly parentId: number | null;
    readonly personalityType: string | null;
    readonly traitPercentages: Record<string, number> | null;
    readonly taskDescription: string;
    readonly preAnalysisQuestions: Array<unknown> | null;
    readonly preAnalysisAnswers: Record<string, string> | null;
    readonly preAnalysisReasoning: string | null;
    readonly preAnalysisSkipped: boolean;
    readonly frameworkQuestions: Array<unknown> | null;
    readonly clarifyingAnswers: Array<unknown> | null;
    readonly optimizedPrompt: string | null;
    readonly workflowStage: string;
    readonly errorMessage: string | null;
    readonly completedAt: string | null;
    readonly createdAt: string;
    readonly updatedAt: string;
    // Prompt Builder specific fields
    readonly taskClassification: Record<string, unknown> | null;
    readonly cognitiveRequirements: Record<string, unknown> | null;
    readonly selectedFramework: Record<string, unknown> | null;
    readonly alternativeFrameworks: Array<unknown> | null;
    readonly personalityTier: string | null;
    readonly taskTraitAlignment: Record<string, unknown> | null;
    readonly personalityAdjustmentsPreview: Array<string> | null;
    readonly questionRationale: string | null;
    readonly frameworkUsed: Record<string, unknown> | null;
    readonly personalityAdjustmentsSummary: Array<string> | null;
    readonly modelRecommendations: Array<unknown> | null;
    readonly iterationSuggestions: Array<string> | null;
    readonly preAnalysisApiUsage: Record<string, unknown> | null;
    readonly analysisApiUsage: Record<string, unknown> | null;
    readonly generationApiUsage: Record<string, unknown> | null;
    // Question ratings
    readonly questionRatings?: Array<{
        questionId: string;
        questionIndex: number;
        rating: number;
        explanation: string | null;
    }>;
    // Relationships
    readonly visitor?: VisitorResource;
    readonly user?: UserResource | null;
    readonly parent?: PromptRunResource | null;
    readonly children?: readonly PromptRunResource[];
}
