import FrameworkSelection from '@/Components/PromptOptimizer/Cards/FrameworkSelection.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('FrameworkSelection', () => {
    const defaultProps = {
        framework: 'SMART Goals',
        reasoning: 'This framework suits your task',
    };

    it('should render framework name and reasoning', () => {
        const wrapper = mount(FrameworkSelection, {
            props: defaultProps,
        });

        expect(wrapper.find('[data-testid="framework-name"]').text()).toBe(
            'SMART Goals',
        );
        expect(wrapper.find('[data-testid="framework-reasoning"]').text()).toBe(
            'This framework suits your task',
        );
    });

    it('should hide personality approach badge when not provided', () => {
        const wrapper = mount(FrameworkSelection, {
            props: defaultProps,
        });

        expect(
            wrapper.find('[data-testid="personality-approach-badge"]').exists(),
        ).toBe(false);
    });

    it('should hide personality approach badge when null', () => {
        const wrapper = mount(FrameworkSelection, {
            props: {
                ...defaultProps,
                personalityApproach: null,
            },
        });

        expect(
            wrapper.find('[data-testid="personality-approach-badge"]').exists(),
        ).toBe(false);
    });

    it('should display amplify badge with correct text and colour', () => {
        const wrapper = mount(FrameworkSelection, {
            props: {
                ...defaultProps,
                personalityApproach: 'amplify',
            },
        });

        const badge = wrapper.find(
            '[data-testid="personality-approach-badge"]',
        );
        expect(badge.exists()).toBe(true);
        expect(badge.text()).toBe('Amplify');
        expect(badge.classes()).toContain('bg-green-100');
        expect(badge.classes()).toContain('text-green-800');
    });

    it('should display counterbalance badge with correct text and colour', () => {
        const wrapper = mount(FrameworkSelection, {
            props: {
                ...defaultProps,
                personalityApproach: 'counterbalance',
            },
        });

        const badge = wrapper.find(
            '[data-testid="personality-approach-badge"]',
        );
        expect(badge.exists()).toBe(true);
        expect(badge.text()).toBe('Counterbalance');
        expect(badge.classes()).toContain('bg-pink-100');
        expect(badge.classes()).toContain('text-pink-800');
    });

    it('should display amplify approach description', () => {
        const wrapper = mount(FrameworkSelection, {
            props: {
                ...defaultProps,
                personalityApproach: 'amplify',
            },
        });

        const approachSection = wrapper.find('.bg-pink-50');
        expect(approachSection.exists()).toBe(true);
        expect(approachSection.text()).toContain('Personality Approach');
        expect(approachSection.text()).toContain(
            'Leveraging your natural personality strengths',
        );
    });

    it('should display counterbalance approach description', () => {
        const wrapper = mount(FrameworkSelection, {
            props: {
                ...defaultProps,
                personalityApproach: 'counterbalance',
            },
        });

        const approachSection = wrapper.find('.bg-pink-50');
        expect(approachSection.exists()).toBe(true);
        expect(approachSection.text()).toContain(
            'Providing structure to compensate for potential blind spots',
        );
    });

    it('should hide approach description section when approach is null', () => {
        const wrapper = mount(FrameworkSelection, {
            props: {
                ...defaultProps,
                personalityApproach: null,
            },
        });

        const approachSection = wrapper.find('.bg-pink-50');
        expect(approachSection.exists()).toBe(false);
    });

    it('should always display reasoning section', () => {
        const wrapper = mount(FrameworkSelection, {
            props: {
                ...defaultProps,
                personalityApproach: null,
            },
        });

        const reasoningSection = wrapper.find('.bg-gray-50');
        expect(reasoningSection.exists()).toBe(true);
        expect(reasoningSection.text()).toContain('Why this framework?');
    });

    it('should have correct data-testid on container', () => {
        const wrapper = mount(FrameworkSelection, {
            props: defaultProps,
        });

        expect(
            wrapper
                .find('[data-testid="framework-selection-display"]')
                .exists(),
        ).toBe(true);
    });
});
