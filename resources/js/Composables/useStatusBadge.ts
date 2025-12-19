import {
    getWorkflowStageLabel,
    isFailedStage,
    isProcessingStage,
} from '@/constants/workflow';
import type { WorkflowStage } from '@/types/resources/PromptRunResource';

export type StatusType = WorkflowStage | string;

export interface StatusConfig {
    label: string;
    colorClass: string;
}

export function useStatusBadge() {
    const getStatusConfig = (workflowStage: StatusType): StatusConfig => {
        // Handle undefined or null status
        if (!workflowStage) {
            return {
                label: 'Unknown',
                colorClass: 'bg-gray-100 text-gray-800',
            };
        }

        // Success state: only 2_completed is the final successful state
        if (workflowStage === '2_completed') {
            return {
                label: 'Completed',
                colorClass: 'bg-green-100 text-green-800',
            };
        }

        // Processing states: 0_processing, 1_processing, 2_processing
        if (isProcessingStage(workflowStage)) {
            return {
                label: getWorkflowStageLabel(workflowStage),
                colorClass: 'bg-yellow-100 text-yellow-800',
            };
        }

        // Awaiting user action: 0_completed, 1_completed (waiting for user input)
        if (workflowStage === '0_completed') {
            return {
                label: 'Awaiting Questions',
                colorClass: 'bg-yellow-100 text-yellow-800',
            };
        }

        if (workflowStage === '1_completed') {
            return {
                label: 'Awaiting Answers',
                colorClass: 'bg-blue-100 text-blue-800',
            };
        }

        // Failed states: 0_failed, 1_failed, 2_failed
        if (isFailedStage(workflowStage)) {
            return {
                label: 'Failed',
                colorClass: 'bg-red-100 text-red-800',
            };
        }

        // Fallback for unknown stages
        return {
            label: getWorkflowStageLabel(workflowStage),
            colorClass: 'bg-gray-100 text-gray-800',
        };
    };

    return {
        getStatusConfig,
    };
}
