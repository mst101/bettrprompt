/**
 * Mock n8n Webhook Responses for E2E Testing
 *
 * Provides realistic webhook payloads that match what n8n workflows actually return,
 * including both happy paths and failure scenarios.
 */

export interface N8nFramework {
    name: string;
    code: string;
    components: string[];
    rationale: string;
}

export interface N8nQuestion {
    id: string;
    question: string;
    purpose: string;
    required: boolean;
}

export interface N8nAnalysisResponse {
    prompt_run_id: number;
    workflow_stage: 'framework_selected';
    status: 'processing';
    selected_framework: N8nFramework;
    framework_questions: N8nQuestion[];
    alternative_frameworks?: Array<{
        name: string;
        code: string;
        when_to_use_instead: string;
    }>;
    task_classification?: {
        primary_category: string;
        secondary_category: string | null;
        complexity: 'simple' | 'moderate' | 'complex';
        classification_reasoning: string;
    };
}

export interface N8nCompletionResponse {
    prompt_run_id: number;
    workflow_stage: 'completed';
    status: 'completed';
    optimized_prompt: string;
}

export interface N8nErrorResponse {
    prompt_run_id: number;
    workflow_stage: 'failed';
    status: 'failed';
    error_message: string;
}

// Common Frameworks Library
const FRAMEWORKS = {
    SMART: {
        name: 'SMART Goals',
        code: 'SMART',
        components: [
            'Specific - Clear and well-defined objectives',
            'Measurable - Quantifiable success metrics',
            'Achievable - Realistic with available resources',
            'Relevant - Aligned with broader goals',
            'Time-bound - Specific deadline or timeline',
        ],
        rationale:
            'Ideal for goal-setting, project planning, and outcome-focused tasks requiring clear metrics',
    },
    RICE: {
        name: 'RICE Prioritisation',
        code: 'RICE',
        components: [
            'Reach - How many users will this impact?',
            'Impact - How significantly will it improve user experience?',
            'Confidence - How certain are you about these estimates?',
            'Effort - How much time and resources are required?',
        ],
        rationale:
            'Perfect for prioritisation decisions, feature evaluation, and resource allocation in product development',
    },
    COAST: {
        name: 'COAST Framework',
        code: 'COAST',
        components: [
            'Context - Background and situation',
            'Objective - What you want to achieve',
            'Audience - Who will receive this?',
            'Story - Narrative and key points',
            'Task - Specific action or call-to-action',
        ],
        rationale:
            'Excellent for marketing content, communication tasks, and narrative-driven projects',
    },
    DESIGN_THINKING: {
        name: 'Design Thinking',
        code: 'DT',
        components: [
            'Empathise - Understand user needs and pain points',
            'Define - Articulate the problem statement',
            'Ideate - Generate creative solutions',
            'Prototype - Create tangible representations',
            'Test - Validate with real users',
        ],
        rationale:
            'Perfect for creative problem-solving, user-centric design, and innovation-focused tasks',
    },
    WATERFALL: {
        name: 'Waterfall Planning',
        code: 'WF',
        components: [
            'Requirements - Define all needs upfront',
            'Design - Detailed system architecture',
            'Implementation - Structured development',
            'Testing - Comprehensive validation',
            'Deployment - Rollout and monitoring',
        ],
        rationale:
            'Best for structured, sequential projects with well-defined requirements and minimal changes',
    },
    AGILE: {
        name: 'Agile Methodology',
        code: 'AGILE',
        components: [
            'Sprint Planning - Prioritise work for iteration',
            'Daily Standup - Communication and blockers',
            'Development - Incremental feature delivery',
            'Testing - Continuous validation',
            'Retrospective - Process improvement',
        ],
        rationale:
            'Ideal for iterative projects, rapid development, and teams that benefit from regular feedback',
    },
};

// Framework Selection Logic (mimics n8n workflow decision)
function selectFramework(
    taskDescription: string,
    personalityType?: string,
): N8nFramework {
    const lowerTask = taskDescription.toLowerCase();

    // Simple keyword-based framework selection
    if (
        lowerTask.includes('priorit') ||
        lowerTask.includes('decide') ||
        lowerTask.includes('feature')
    ) {
        return FRAMEWORKS.RICE;
    }
    if (
        lowerTask.includes('goal') ||
        lowerTask.includes('objective') ||
        lowerTask.includes('plan')
    ) {
        return FRAMEWORKS.SMART;
    }
    if (
        lowerTask.includes('market') ||
        lowerTask.includes('content') ||
        lowerTask.includes('campaign') ||
        lowerTask.includes('email') ||
        lowerTask.includes('article')
    ) {
        return FRAMEWORKS.COAST;
    }
    if (
        lowerTask.includes('design') ||
        lowerTask.includes('creative') ||
        lowerTask.includes('problem')
    ) {
        return FRAMEWORKS.DESIGN_THINKING;
    }
    if (
        lowerTask.includes('develop') ||
        lowerTask.includes('build') ||
        lowerTask.includes('implement')
    ) {
        // Personality-based selection
        if (personalityType?.includes('P')) {
            return FRAMEWORKS.AGILE;
        }
        return FRAMEWORKS.WATERFALL;
    }

    // Default to SMART
    return FRAMEWORKS.SMART;
}

// Generate Questions based on Framework
function generateQuestions(framework: N8nFramework): N8nQuestion[] {
    const questions: N8nQuestion[] = [];

    switch (framework.code) {
        case 'SMART':
            questions.push(
                {
                    id: 'Q1',
                    question: 'What is the specific goal you want to achieve?',
                    purpose:
                        'Define the objective clearly to ensure it is specific and actionable',
                    required: true,
                },
                {
                    id: 'Q2',
                    question: 'How will you measure success?',
                    purpose:
                        'Establish measurable criteria to track progress and completion',
                    required: true,
                },
                {
                    id: 'Q3',
                    question: 'What is your timeline for achieving this goal?',
                    purpose:
                        'Set a time-bound constraint to create urgency and accountability',
                    required: false,
                },
            );
            break;

        case 'RICE':
            questions.push(
                {
                    id: 'Q1',
                    question: 'How many users will this impact?',
                    purpose: 'Estimate the reach to evaluate market impact',
                    required: true,
                },
                {
                    id: 'Q2',
                    question:
                        'What level of impact will this have (High/Medium/Low)?',
                    purpose:
                        'Assess the significance of improvement for affected users',
                    required: true,
                },
                {
                    id: 'Q3',
                    question:
                        'How confident are you in these estimates (0-100%)?',
                    purpose:
                        'Account for uncertainty in prioritisation calculations',
                    required: true,
                },
                {
                    id: 'Q4',
                    question: 'How much effort (time/resources) is required?',
                    purpose:
                        'Evaluate resource requirements for accurate prioritisation',
                    required: true,
                },
            );
            break;

        case 'COAST':
            questions.push(
                {
                    id: 'Q1',
                    question: 'Who is your target audience?',
                    purpose: 'Define the specific audience to tailor messaging',
                    required: true,
                },
                {
                    id: 'Q2',
                    question: 'What is the key objective of your message?',
                    purpose:
                        'Clarify the primary goal (inform, persuade, inspire, etc.)',
                    required: true,
                },
                {
                    id: 'Q3',
                    question:
                        'What key points or story elements should be included?',
                    purpose:
                        'Identify the narrative and supporting details needed',
                    required: true,
                },
            );
            break;

        case 'DT':
            questions.push(
                {
                    id: 'Q1',
                    question:
                        'Who is the end user or customer you are designing for?',
                    purpose:
                        'Establish empathy by understanding user needs and pain points',
                    required: true,
                },
                {
                    id: 'Q2',
                    question:
                        'What is the core problem you are trying to solve?',
                    purpose: 'Define the problem statement clearly',
                    required: true,
                },
                {
                    id: 'Q3',
                    question:
                        'What constraints (budget, time, tech) should be considered?',
                    purpose: 'Understand limitations for realistic ideation',
                    required: false,
                },
            );
            break;

        case 'WF':
            questions.push(
                {
                    id: 'Q1',
                    question: 'What are all the requirements for this project?',
                    purpose: 'Document all needs upfront before design begins',
                    required: true,
                },
                {
                    id: 'Q2',
                    question:
                        'What is the project timeline and key milestones?',
                    purpose:
                        'Establish a detailed schedule for sequential phases',
                    required: true,
                },
                {
                    id: 'Q3',
                    question:
                        'What are the success criteria and acceptance tests?',
                    purpose:
                        'Define validation standards before implementation',
                    required: true,
                },
            );
            break;

        case 'AGILE':
            questions.push(
                {
                    id: 'Q1',
                    question: 'What is your sprint duration (1-4 weeks)?',
                    purpose: 'Establish iteration rhythm for feedback cycles',
                    required: true,
                },
                {
                    id: 'Q2',
                    question: 'Who are the key stakeholders for feedback?',
                    purpose: 'Identify reviewers for continuous validation',
                    required: true,
                },
                {
                    id: 'Q3',
                    question:
                        'What are your top 3 priorities for the first sprint?',
                    purpose: 'Backlog prioritisation for incremental delivery',
                    required: true,
                },
            );
            break;

        default:
            // Fallback questions
            questions.push({
                id: 'Q1',
                question: 'What are your main goals for this task?',
                purpose: 'Clarify objectives and desired outcomes',
                required: true,
            });
    }

    return questions;
}

/**
 * Mock successful framework selection response
 * Simulates what n8n workflow_1 returns after analysis
 */
export function mockFrameworkSelectionResponse(
    promptRunId: number,
    taskDescription: string,
    personalityType?: string,
): N8nAnalysisResponse {
    const framework = selectFramework(taskDescription, personalityType);
    const questions = generateQuestions(framework);

    return {
        prompt_run_id: promptRunId,
        workflow_stage: 'framework_selected',
        status: 'processing',
        selected_framework: framework,
        framework_questions: questions,
        alternative_frameworks: [
            {
                name: 'Alternative 1',
                code: 'ALT1',
                when_to_use_instead: 'When you need a different approach',
            },
        ],
        task_classification: {
            primary_category: 'PLANNING',
            secondary_category: null,
            complexity: taskDescription.length > 200 ? 'complex' : 'moderate',
            classification_reasoning:
                'Task classified based on keywords and characteristics',
        },
    };
}

/**
 * Mock successful prompt generation response
 * Simulates what n8n workflow_2 returns after generation
 */
export function mockPromptGenerationResponse(
    promptRunId: number,
    taskDescription: string,
    selectedFramework?: N8nFramework,
    answers?: string[],
): N8nCompletionResponse {
    const framework = selectedFramework || selectFramework(taskDescription);

    // Build a realistic optimized prompt
    let optimizedPrompt = `# Optimised Prompt for Your Task\n\n`;
    optimizedPrompt += `## Task\n${taskDescription}\n\n`;
    optimizedPrompt += `## Framework: ${framework.name}\n`;
    optimizedPrompt += `This prompt uses the **${framework.name}** framework with these components:\n`;
    framework.components.forEach((component) => {
        optimizedPrompt += `- ${component}\n`;
    });

    if (answers && answers.length > 0) {
        optimizedPrompt += `\n## Context from Your Answers\n`;
        answers.forEach((answer, index) => {
            optimizedPrompt += `- Answer ${index + 1}: ${answer}\n`;
        });
    }

    optimizedPrompt += `\n## Instructions\n`;
    optimizedPrompt += `Please help me with the above task, considering the context provided. `;
    optimizedPrompt += `Use the ${framework.name} framework to structure your response.`;

    return {
        prompt_run_id: promptRunId,
        workflow_stage: 'completed',
        status: 'completed',
        optimized_prompt: optimizedPrompt,
    };
}

/**
 * Mock timeout failure
 * Simulates n8n workflow timing out (e.g., API rate limit, network issue)
 */
export function mockTimeoutError(promptRunId: number): N8nErrorResponse {
    return {
        prompt_run_id: promptRunId,
        workflow_stage: 'failed',
        status: 'failed',
        error_message:
            'Workflow execution timeout after 60 seconds. Please try again.',
    };
}

/**
 * Mock API error response
 * Simulates n8n failing to reach external API
 */
export function mockApiError(promptRunId: number): N8nErrorResponse {
    return {
        prompt_run_id: promptRunId,
        workflow_stage: 'failed',
        status: 'failed',
        error_message:
            'Failed to fetch reference data from API. External service unavailable.',
    };
}

/**
 * Mock invalid input error
 * Simulates n8n validation failure
 */
export function mockValidationError(
    promptRunId: number,
    field: string,
): N8nErrorResponse {
    return {
        prompt_run_id: promptRunId,
        workflow_stage: 'failed',
        status: 'failed',
        error_message: `Invalid input: Missing or invalid '${field}' field`,
    };
}

/**
 * Mock rate limiting error
 * Simulates LLM API rate limit (e.g., OpenAI quota exceeded)
 */
export function mockRateLimitError(promptRunId: number): N8nErrorResponse {
    return {
        prompt_run_id: promptRunId,
        workflow_stage: 'failed',
        status: 'failed',
        error_message:
            'Rate limit exceeded. Please wait before retrying. Try again in 60 seconds.',
    };
}
