/* eslint-disable @typescript-eslint/no-explicit-any */
import { useStatusBadge } from '@/Composables/useStatusBadge';
import { describe, expect, it } from 'vitest';

describe('useStatusBadge', () => {
    it('should return config for completed workflow stage (2_completed)', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig('2_completed');

        expect(config.label).toBe('Completed');
        expect(config.colorClass).toBe('bg-green-100 text-green-800');
    });

    it('should return config for processing workflow stage (1_processing)', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig('1_processing');

        expect(config.label).toContain('Analysing');
        expect(config.colorClass).toBe('bg-yellow-100 text-yellow-800');
    });

    it('should return config for pre-analysis processing stage (0_processing)', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig('0_processing');

        expect(config.label).toContain('Pre-Analysis');
        expect(config.colorClass).toBe('bg-yellow-100 text-yellow-800');
    });

    it('should return config for failed workflow stage (2_failed)', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig('2_failed');

        expect(config.label).toBe('Failed');
        expect(config.colorClass).toBe('bg-red-100 text-red-800');
    });

    it('should return config for pending workflow stage (1_completed)', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig('1_completed');

        expect(config.label).toBe('Pending');
        expect(config.colorClass).toBe('bg-gray-100 text-gray-800');
    });

    it('should return config for pre-analysis completed stage (0_completed)', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig('0_completed');

        expect(config.label).toBe('Pending');
        expect(config.colorClass).toBe('bg-gray-100 text-gray-800');
    });

    it('should handle unknown workflow stage gracefully', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig('unknown-stage');

        expect(config.colorClass).toBe('bg-gray-100 text-gray-800');
    });

    it('should handle null workflow stage', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig(null as any);

        expect(config.label).toBe('Unknown');
        expect(config.colorClass).toBe('bg-gray-100 text-gray-800');
    });

    it('should handle undefined workflow stage', () => {
        const { getStatusConfig } = useStatusBadge();
        const config = getStatusConfig(undefined as any);

        expect(config.label).toBe('Unknown');
        expect(config.colorClass).toBe('bg-gray-100 text-gray-800');
    });

    it('should recognise all processing stages', () => {
        const { getStatusConfig } = useStatusBadge();

        expect(getStatusConfig('0_processing').colorClass).toContain('yellow');
        expect(getStatusConfig('1_processing').colorClass).toContain('yellow');
        expect(getStatusConfig('2_processing').colorClass).toContain('yellow');
    });

    it('should recognise all failed stages', () => {
        const { getStatusConfig } = useStatusBadge();

        expect(getStatusConfig('0_failed').colorClass).toContain('red');
        expect(getStatusConfig('1_failed').colorClass).toContain('red');
        expect(getStatusConfig('2_failed').colorClass).toContain('red');
    });
});
