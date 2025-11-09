import StatusBadge from '@/Components/StatusBadge.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('StatusBadge', () => {
    it('should render completed status', () => {
        const wrapper = mount(StatusBadge, {
            props: { status: 'completed' },
        });

        expect(wrapper.text()).toBe('Completed');
        expect(wrapper.classes()).toContain('bg-green-100');
        expect(wrapper.classes()).toContain('text-green-800');
    });

    it('should render processing status', () => {
        const wrapper = mount(StatusBadge, {
            props: { status: 'processing' },
        });

        expect(wrapper.text()).toBe('Processing');
        expect(wrapper.classes()).toContain('bg-yellow-100');
        expect(wrapper.classes()).toContain('text-yellow-800');
    });

    it('should render failed status', () => {
        const wrapper = mount(StatusBadge, {
            props: { status: 'failed' },
        });

        expect(wrapper.text()).toBe('Failed');
        expect(wrapper.classes()).toContain('bg-red-100');
        expect(wrapper.classes()).toContain('text-red-800');
    });

    it('should render pending status', () => {
        const wrapper = mount(StatusBadge, {
            props: { status: 'pending' },
        });

        expect(wrapper.text()).toBe('Pending');
        expect(wrapper.classes()).toContain('bg-gray-100');
        expect(wrapper.classes()).toContain('text-gray-800');
    });

    it('should have test ID attribute', () => {
        const wrapper = mount(StatusBadge, {
            props: { status: 'completed' },
        });

        expect(wrapper.attributes('data-testid')).toBe('status-badge');
    });

    it('should have data-test-status attribute', () => {
        const wrapper = mount(StatusBadge, {
            props: { status: 'processing' },
        });

        expect(wrapper.attributes('data-test-status')).toBe('processing');
    });

    it('should be uppercase', () => {
        const wrapper = mount(StatusBadge, {
            props: { status: 'completed' },
        });

        expect(wrapper.classes()).toContain('uppercase');
    });

    it('should be inline-flex', () => {
        const wrapper = mount(StatusBadge, {
            props: { status: 'completed' },
        });

        expect(wrapper.classes()).toContain('inline-flex');
    });

    it('should have rounded-full class', () => {
        const wrapper = mount(StatusBadge, {
            props: { status: 'completed' },
        });

        expect(wrapper.classes()).toContain('rounded-full');
    });

    it('should handle custom status', () => {
        const wrapper = mount(StatusBadge, {
            props: { status: 'custom-status' },
        });

        expect(wrapper.text()).toBe('Custom-status');
        expect(wrapper.classes()).toContain('bg-gray-100');
    });
});
