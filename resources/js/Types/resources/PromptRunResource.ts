/**
 * PromptRunResource - Primary type for PromptRun data
 *
 * ✅ Use this type for:
 * - Component props receiving prompt run data from backend
 * - WebSocket/realtime updates
 * - API responses
 *
 * This is auto-generated from app/Http/Resources/PromptRunResource.php.
 * DO NOT modify directly - update the PHP docblock and run:
 * php artisan bp:types:generate
 *
 * @see PromptRun for base model type (rarely used)
 * @see PromptRunPageResource for lightweight page load variant (internal use only)
 */

import type { UserResource, VisitorResource } from '@/Types';
import type {
    AlternativeFramework,
    ApiUsage,
    CognitiveRequirements,
    ModelRecommendation,
    PersonalityTier,
    SelectedFramework,
    TaskClassification,
    TaskTraitAlignment,
} from '@/Types/models/PromptData';

export interface PromptRunResource {
    readonly id: number;
    readonly userId: number | null;
    readonly visitorId: string;
    readonly parentId: number | null;
    readonly personalityType: string | null;
    readonly traitPercentages: Record<string, number> | null;
    readonly taskDescription: string;
    readonly preAnalysisQuestions: Array<Record<string, unknown>> | null;
    readonly preAnalysisAnswers: Record<string, string> | null;
    readonly preAnalysisReasoning: string | null;
    readonly preAnalysisSkipped: boolean;
    readonly frameworkQuestions: Array<Record<string, unknown>> | null;
    readonly clarifyingAnswers: Array<Record<string, unknown>> | null;
    readonly optimizedPrompt: string | null;
    readonly workflowStage: string;
    readonly errorMessage: string | null;
    readonly completedAt: string | null;
    readonly createdAt: string;
    readonly updatedAt: string;
    // Prompt Builder specific fields
    readonly taskClassification: TaskClassification | null;
    readonly cognitiveRequirements: CognitiveRequirements | null;
    readonly selectedFramework: SelectedFramework | null;
    readonly alternativeFrameworks: AlternativeFramework[] | null;
    readonly personalityTier: PersonalityTier | null;
    readonly taskTraitAlignment: TaskTraitAlignment | null;
    readonly personalityAdjustmentsPreview: Array<string> | null;
    readonly questionRationale: string | null;
    readonly frameworkUsed: SelectedFramework | null;
    readonly personalityAdjustmentsSummary: Array<string> | null;
    readonly modelRecommendations: ModelRecommendation[] | null;
    readonly iterationSuggestions: Array<string> | null;
    readonly preAnalysisApiUsage: ApiUsage | ApiUsage[] | null;
    readonly analysisApiUsage: ApiUsage | ApiUsage[] | null;
    readonly generationApiUsage: ApiUsage | ApiUsage[] | null;
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
