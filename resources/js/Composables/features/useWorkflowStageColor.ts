/**
 * @deprecated Use `useStatusBadge` instead for consistent status handling
 * Gets the colour class for a workflow stage badge
 * Extracts the stage status part from the workflow stage (e.g., "1_processing" -> "processing")
 */
export const useWorkflowStageColor = () => {
    const getWorkflowStageColor = (
        workflowStage: string | undefined | null,
    ): string => {
        // Handle undefined/null values
        if (!workflowStage) {
            return 'bg-indigo-100 text-indigo-800';
        }

        // Extract the stage status part from workflow stage (e.g., "1_processing" -> "processing")
        const stagePart = workflowStage.split('_').pop() || '';
        const colors: Record<string, string> = {
            processing: 'bg-blue-100 text-blue-800',
            completed: 'bg-green-100 text-green-800',
            failed: 'bg-red-100 text-red-800',
        };
        return colors[stagePart] || 'bg-indigo-100 text-indigo-800';
    };

    return {
        getWorkflowStageColor,
    };
};
