/**
 * Gets the colour class for a workflow stage badge
 * Extracts the status part from the workflow stage (e.g., "1_processing" -> "processing")
 */
export const useWorkflowStageColor = () => {
    const getStatusColor = (workflowStage: string): string => {
        // Extract the status part from workflow stage (e.g., "1_processing" -> "processing")
        const statusPart = workflowStage.split('_').pop() || '';
        const colors: Record<string, string> = {
            processing: 'bg-blue-100 text-blue-800',
            completed: 'bg-green-100 text-green-800',
            failed: 'bg-red-100 text-red-800',
        };
        return colors[statusPart] || 'bg-indigo-100 text-indigo-800';
    };

    return {
        getStatusColor,
    };
};
