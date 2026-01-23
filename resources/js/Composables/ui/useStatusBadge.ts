import { isFailedStage, isProcessingStage } from '@/Constants/workflow';
import type { WorkflowStage } from '@/Types/resources/PromptRunResource';

export type StatusType = WorkflowStage | string;

export interface StatusConfig {
    labelKey: string;
    colorClass: string;
}

export function useStatusBadge() {
    const getStatusConfig = (workflowStage: StatusType): StatusConfig => {
        // Handle undefined or null status
        if (!workflowStage) {
            return {
                labelKey: 'status.unknown',
                colorClass: 'bg-indigo-100 text-indigo-900',
            };
        }

        // Success state: only 2_completed is the final successful state
        if (workflowStage === '2_completed') {
            return {
                labelKey: 'status.completed',
                colorClass: 'bg-green-100 text-green-900',
            };
        }

        // Processing states: 0_processing, 1_processing, 2_processing
        if (isProcessingStage(workflowStage)) {
            return {
                labelKey: 'status.processing',
                colorClass: 'bg-yellow-400 text-yellow-900 dark:text-yellow-50',
            };
        }

        // Awaiting user action: 0_completed, 1_completed (waiting for user input)
        if (workflowStage === '0_completed') {
            return {
                labelKey: 'status.awaitingQuestions',
                colorClass: 'bg-yellow-400 text-yellow-900 dark:text-yellow-50',
            };
        }

        if (workflowStage === '1_completed') {
            return {
                labelKey: 'status.awaitingAnswers',
                colorClass:
                    'bg-blue-200 text-blue-800 dark:bg-blue-400 dark:text-blue-900',
            };
        }

        // Failed states: 0_failed, 1_failed, 2_failed
        if (isFailedStage(workflowStage)) {
            return {
                labelKey: 'status.failed',
                colorClass: 'bg-red-100 text-red-900',
            };
        }

        // Fallback for unknown stages
        return {
            labelKey: 'status.unknown',
            colorClass: 'bg-indigo-100 text-indigo-900',
        };
    };

    return {
        getStatusConfig,
    };
}
