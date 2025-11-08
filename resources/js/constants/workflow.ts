/**
 * Workflow stage constants for prompt optimization
 */
export const WORKFLOW_STAGES = {
    SUBMITTED: 'submitted',
    FRAMEWORK_SELECTED: 'framework_selected',
    ANSWERING_QUESTIONS: 'answering_questions',
    GENERATING_PROMPT: 'generating_prompt',
    COMPLETED: 'completed',
    FAILED: 'failed',
} as const;

export type WorkflowStage =
    (typeof WORKFLOW_STAGES)[keyof typeof WORKFLOW_STAGES];

/**
 * Prompt run status constants
 */
export const PROMPT_RUN_STATUS = {
    PENDING: 'pending',
    PROCESSING: 'processing',
    COMPLETED: 'completed',
    FAILED: 'failed',
} as const;

export type PromptRunStatus =
    (typeof PROMPT_RUN_STATUS)[keyof typeof PROMPT_RUN_STATUS];

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
        case WORKFLOW_STAGES.SUBMITTED:
            return 'Submitted';
        case WORKFLOW_STAGES.FRAMEWORK_SELECTED:
            return 'Framework Selected';
        case WORKFLOW_STAGES.ANSWERING_QUESTIONS:
            return 'Answering Questions';
        case WORKFLOW_STAGES.GENERATING_PROMPT:
            return 'Generating Prompt';
        case WORKFLOW_STAGES.COMPLETED:
            return 'Completed';
        case WORKFLOW_STAGES.FAILED:
            return 'Failed';
        default:
            return stage;
    }
}
