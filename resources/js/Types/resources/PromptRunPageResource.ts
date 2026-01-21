/**
 * TypeScript definition for PromptRunPageResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

import type {
    PromptRunRelationshipResource,
    UserResource,
    VisitorResource,
} from '@/Types';

export interface PromptRunPageResource {
    readonly id: number;
    readonly userId: number | null;
    readonly visitorId: string | null;
    readonly parentId: number | null;
    readonly personalityType: string | null;
    readonly traitPercentages: {
        mind: number | null;
        energy: number | null;
        nature: number | null;
        tactics: number | null;
        identity: number | null;
    } | null;
    readonly taskDescription: string;
    readonly preAnalysisQuestions: Array<{ question: string }> | null;
    readonly preAnalysisAnswers: string[] | null;
    readonly preAnalysisReasoning: string | null;
    readonly preAnalysisSkipped: boolean;
    readonly frameworkQuestions: Array<{
        question: string;
        context?: string;
        cognitive_requirements?: string[];
    }> | null;
    readonly clarifyingAnswers: string[] | null;
    readonly currentQuestionIndex: number | null;
    readonly optimizedPrompt: string | null;
    readonly workflowStage: string;
    readonly errorMessage: string | null;
    readonly completedAt: string | null;
    readonly createdAt: string | null;
    readonly updatedAt: string | null;
    readonly taskClassification: {
        category?: string;
        complexity?: string;
        domain?: string;
    } | null;
    readonly cognitiveRequirements: string[] | null;
    readonly selectedFramework: {
        name?: string;
        score?: number;
        reasoning?: string;
    } | null;
    readonly alternativeFrameworks: Array<{
        name?: string;
        score?: number;
        reasoning?: string;
    }> | null;
    readonly personalityTier: string | null;
    readonly taskTraitAlignment: Record<string, any> | null;
    readonly personalityAdjustmentsPreview: Record<string, any> | null;
    readonly questionRationale: string | null;
    readonly frameworkUsed: Record<string, any> | null;
    readonly personalityAdjustmentsSummary: Record<string, any> | null;
    readonly modelRecommendations: Record<string, any> | null;
    readonly iterationSuggestions: string[] | null;
    readonly preAnalysisApiUsage: {
        inputTokens?: number;
        outputTokens?: number;
        totalCost?: number;
    } | null;
    readonly analysisApiUsage: {
        inputTokens?: number;
        outputTokens?: number;
        totalCost?: number;
    } | null;
    readonly generationApiUsage: {
        inputTokens?: number;
        outputTokens?: number;
        totalCost?: number;
    } | null;
    readonly questionRatings?: Array<{
        questionId: string;
        questionIndex: number;
        rating: number;
        explanation: string | null;
    }>;
    readonly user?: UserResource | null;
    readonly visitor?: VisitorResource | null;
    readonly parent?: PromptRunRelationshipResource | null;
    readonly children?: PromptRunRelationshipResource[];
}
