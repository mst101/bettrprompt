import { useStatusBadge } from '@/Composables/useStatusBadge';
import { describe, expect, it } from 'vitest';

describe('useStatusBadge', () => {
    it('should return config for completed status', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig('completed');

        expect(config.label).toBe('Completed');
        expect(config.colorClass).toBe('bg-green-100 text-green-800');
    });

    it('should return config for processing status', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig('processing');

        expect(config.label).toBe('Clarifying Questions');
        expect(config.colorClass).toBe('bg-yellow-100 text-yellow-800');
    });

    it('should return config for failed status', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig('failed');

        expect(config.label).toBe('Failed');
        expect(config.colorClass).toBe('bg-red-100 text-red-800');
    });

    it('should return config for pending status', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig('pending');

        expect(config.label).toBe('Pending');
        expect(config.colorClass).toBe('bg-gray-100 text-gray-800');
    });

    it('should handle unknown status gracefully', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig('custom-status');

        expect(config.label).toBe('Custom-status');
        expect(config.colorClass).toBe('bg-gray-100 text-gray-800');
    });

    it('should handle null status', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig(null as any);

        expect(config.label).toBe('Unknown');
        expect(config.colorClass).toBe('bg-gray-100 text-gray-800');
    });

    it('should handle undefined status', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig(undefined as any);

        expect(config.label).toBe('Unknown');
        expect(config.colorClass).toBe('bg-gray-100 text-gray-800');
    });

    it('should capitalise first letter of custom status', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig('waiting');

        expect(config.label).toBe('Waiting');
    });
});
