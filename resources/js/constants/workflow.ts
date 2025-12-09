/**
 * 9-stage workflow system: 3 workflows × 3 states each
 * Workflow 0 (Pre-analysis) → Workflow 1 (Analysis) → Workflow 2 (Generation)
 */
export const WORKFLOW_STAGES = {
    // Workflow 0: Pre-analysis
    PRE_ANALYSIS_PROCESSING: '0_processing',
    PRE_ANALYSIS_COMPLETED: '0_completed',
    PRE_ANALYSIS_FAILED: '0_failed',
    // Workflow 1: Main analysis
    ANALYSIS_PROCESSING: '1_processing',
    ANALYSIS_COMPLETED: '1_completed',
    ANALYSIS_FAILED: '1_failed',
    // Workflow 2: Prompt generation
    GENERATION_PROCESSING: '2_processing',
    GENERATION_COMPLETED: '2_completed',
    GENERATION_FAILED: '2_failed',
} as const;

export type WorkflowStage =
    (typeof WORKFLOW_STAGES)[keyof typeof WORKFLOW_STAGES];

/**
 * Personality type constants (MBTI)
 */
export const PERSONALITY_TYPES = {
    INTJ: 'INTJ',
    INTP: 'INTP',
    ENTJ: 'ENTJ',
    ENTP: 'ENTP',
    INFJ: 'INFJ',
    INFP: 'INFP',
    ENFJ: 'ENFJ',
    ENFP: 'ENFP',
    ISTJ: 'ISTJ',
    ISFJ: 'ISFJ',
    ESTJ: 'ESTJ',
    ESFJ: 'ESFJ',
    ISTP: 'ISTP',
    ISFP: 'ISFP',
    ESTP: 'ESTP',
    ESFP: 'ESFP',
} as const;

export type PersonalityType =
    (typeof PERSONALITY_TYPES)[keyof typeof PERSONALITY_TYPES];

/**
 * Personality type names/descriptions
 */
export const PERSONALITY_TYPE_NAMES: Record<PersonalityType, string> = {
    INTJ: 'Architect',
    INTP: 'Logician',
    ENTJ: 'Commander',
    ENTP: 'Debater',
    INFJ: 'Advocate',
    INFP: 'Mediator',
    ENFJ: 'Protagonist',
    ENFP: 'Campaigner',
    ISTJ: 'Logistician',
    ISFJ: 'Defender',
    ESTJ: 'Executive',
    ESFJ: 'Consul',
    ISTP: 'Virtuoso',
    ISFP: 'Adventurer',
    ESTP: 'Entrepreneur',
    ESFP: 'Entertainer',
};

/**
 * Get human-readable label for workflow stage
 */
export function getWorkflowStageLabel(stage: WorkflowStage | string): string {
    switch (stage) {
        // Workflow 0: Pre-analysis
        case WORKFLOW_STAGES.PRE_ANALYSIS_PROCESSING:
            return 'Pre-Analysis Running';
        case WORKFLOW_STAGES.PRE_ANALYSIS_COMPLETED:
            return 'Pre-Analysis Complete';
        case WORKFLOW_STAGES.PRE_ANALYSIS_FAILED:
            return 'Pre-Analysis Failed';
        // Workflow 1: Main analysis
        case WORKFLOW_STAGES.ANALYSIS_PROCESSING:
            return 'Analysing Task';
        case WORKFLOW_STAGES.ANALYSIS_COMPLETED:
            return 'Analysis Complete';
        case WORKFLOW_STAGES.ANALYSIS_FAILED:
            return 'Analysis Failed';
        // Workflow 2: Prompt generation
        case WORKFLOW_STAGES.GENERATION_PROCESSING:
            return 'Generating Prompt';
        case WORKFLOW_STAGES.GENERATION_COMPLETED:
            return 'Completed';
        case WORKFLOW_STAGES.GENERATION_FAILED:
            return 'Generation Failed';
        default:
            return stage;
    }
}

/**
 * Check if a workflow stage is processing (background job running)
 */
export function isProcessingStage(stage: WorkflowStage | string): boolean {
    return ['0_processing', '1_processing', '2_processing'].includes(stage);
}

/**
 * Check if a workflow stage is failed
 */
export function isFailedStage(stage: WorkflowStage | string): boolean {
    return ['0_failed', '1_failed', '2_failed'].includes(stage);
}
