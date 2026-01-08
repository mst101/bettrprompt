import StatusBadge from '@/Components/Common/StatusBadge.vue';
import enUS from '@/i18n/locales/en-US.json';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('StatusBadge', () => {
    it('should render completed workflow stage (2_completed)', () => {
        const wrapper = mount(StatusBadge, {
            props: { workflowStage: '2_completed' },
        });

        expect(wrapper.text()).toBe(enUS.status.completed);
        expect(wrapper.classes()).toContain('bg-green-100');
        expect(wrapper.classes()).toContain('text-green-900');
    });

    it('should render processing workflow stage (1_processing)', () => {
        const wrapper = mount(StatusBadge, {
            props: { workflowStage: '1_processing' },
        });

        expect(wrapper.text()).toContain(enUS.status.processing);
        expect(wrapper.classes()).toContain('bg-yellow-400');
        expect(wrapper.classes()).toContain('text-yellow-900');
    });

    it('should render failed workflow stage (2_failed)', () => {
        const wrapper = mount(StatusBadge, {
            props: { workflowStage: '2_failed' },
        });

        expect(wrapper.text()).toBe(enUS.status.failed);
        expect(wrapper.classes()).toContain('bg-red-100');
        expect(wrapper.classes()).toContain('text-red-900');
    });

    it('should render awaiting answers workflow stage (1_completed)', () => {
        const wrapper = mount(StatusBadge, {
            props: { workflowStage: '1_completed' },
        });

        expect(wrapper.text()).toBe(enUS.status.awaitingAnswers);
        expect(wrapper.classes()).toContain('bg-blue-200');
        expect(wrapper.classes()).toContain('text-blue-800');
    });

    it('should render awaiting questions stage (0_completed)', () => {
        const wrapper = mount(StatusBadge, {
            props: { workflowStage: '0_completed' },
        });

        expect(wrapper.text()).toBe(enUS.status.awaitingQuestions);
        expect(wrapper.classes()).toContain('bg-yellow-400');
        expect(wrapper.classes()).toContain('text-yellow-900');
    });

    it('should have test ID attribute', () => {
        const wrapper = mount(StatusBadge, {
            props: { workflowStage: '2_completed' },
        });

        expect(wrapper.attributes('data-testid')).toBe('status-badge');
    });

    it('should have data-test-workflow-stage attribute', () => {
        const wrapper = mount(StatusBadge, {
            props: { workflowStage: '1_processing' },
        });

        expect(wrapper.attributes('data-test-workflow-stage')).toBe(
            '1_processing',
        );
    });

    it('should be uppercase', () => {
        const wrapper = mount(StatusBadge, {
            props: { workflowStage: '2_completed' },
        });

        expect(wrapper.classes()).toContain('uppercase');
    });

    it('should be inline-flex', () => {
        const wrapper = mount(StatusBadge, {
            props: { workflowStage: '2_completed' },
        });

        expect(wrapper.classes()).toContain('inline-flex');
    });

    it('should have rounded-full class', () => {
        const wrapper = mount(StatusBadge, {
            props: { workflowStage: '2_completed' },
        });

        expect(wrapper.classes()).toContain('rounded-full');
    });

    it('should handle unknown workflow stage', () => {
        const wrapper = mount(StatusBadge, {
            props: { workflowStage: 'unknown' },
        });

        expect(wrapper.classes()).toContain('bg-indigo-100');
    });
});
