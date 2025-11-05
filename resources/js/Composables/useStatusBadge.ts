export type StatusType = 'completed' | 'processing' | 'failed' | 'pending' | string;

export interface StatusConfig {
    label: string;
    colorClass: string;
}

export function useStatusBadge() {
    const getStatusConfig = (status: StatusType): StatusConfig => {
        switch (status) {
            case 'completed':
                return {
                    label: 'Completed',
                    colorClass: 'bg-green-100 text-green-800',
                };
            case 'processing':
                return {
                    label: 'Processing',
                    colorClass: 'bg-yellow-100 text-yellow-800',
                };
            case 'failed':
                return {
                    label: 'Failed',
                    colorClass: 'bg-red-100 text-red-800',
                };
            case 'pending':
                return {
                    label: 'Pending',
                    colorClass: 'bg-gray-100 text-gray-800',
                };
            default:
                return {
                    label: status.charAt(0).toUpperCase() + status.slice(1),
                    colorClass: 'bg-gray-100 text-gray-800',
                };
        }
    };

    return {
        getStatusConfig,
    };
}
