import ButtonDanger from '@/Components/ButtonDanger.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import WorkflowError from '@/Components/PromptBuilder/WorkflowError.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('WorkflowError', () => {
    const defaultProps = {
        errorMessage: 'Something went wrong',
    };

    it('should render error message', () => {
        const wrapper = mount(WorkflowError, {
            props: defaultProps,
        });

        expect(wrapper.text()).toContain('Something went wrong');
    });

    it('should render "Workflow Failed" heading', () => {
        const wrapper = mount(WorkflowError, {
            props: defaultProps,
        });

        expect(wrapper.find('h3').text()).toBe('Workflow Failed');
    });

    it('should render DynamicIcon for error', () => {
        const wrapper = mount(WorkflowError, {
            props: defaultProps,
            global: {
                stubs: {
                    DynamicIcon: true,
                },
            },
        });

        const icons = wrapper.findAllComponents(DynamicIcon);
        expect(icons.length).toBe(2);
        expect(icons[0].props('name')).toBe('exclamation-circle');
    });

    it('should render DynamicIcon for retry button', () => {
        const wrapper = mount(WorkflowError, {
            props: defaultProps,
            global: {
                stubs: {
                    DynamicIcon: true,
                },
            },
        });

        const icons = wrapper.findAllComponents(DynamicIcon);
        expect(icons.length).toBe(2);
        expect(icons[1].props('name')).toBe('arrow-path');
    });

    it('should render retry button', () => {
        const wrapper = mount(WorkflowError, {
            props: defaultProps,
        });

        const button = wrapper.findComponent(ButtonDanger);
        expect(button.exists()).toBe(true);
        expect(button.text()).toContain('Retry Workflow');
    });

    it('should emit retry event when button clicked', async () => {
        const wrapper = mount(WorkflowError, {
            props: defaultProps,
        });

        const button = wrapper.findComponent(ButtonDanger);
        await button.trigger('click');

        expect(wrapper.emitted('retry')).toBeTruthy();
        expect(wrapper.emitted('retry')?.length).toBe(1);
    });

    it('should have red border and background', () => {
        const wrapper = mount(WorkflowError, {
            props: defaultProps,
        });

        const container = wrapper.find('div');
        expect(container.classes()).toContain('border-red-300');
        expect(container.classes()).toContain('bg-red-50');
    });

    it('should have red error icon', () => {
        const wrapper = mount(WorkflowError, {
            props: defaultProps,
            global: {
                stubs: {
                    DynamicIcon: true,
                },
            },
        });

        const errorIcon = wrapper.findAllComponents(DynamicIcon)[0];
        expect(errorIcon.classes()).toContain('text-red-600');
    });

    it('should have responsive layout classes', () => {
        const wrapper = mount(WorkflowError, {
            props: defaultProps,
        });

        const innerDiv = wrapper.find('.flex.flex-col');
        expect(innerDiv.classes()).toContain('sm:flex-row');
        expect(innerDiv.classes()).toContain('sm:items-start');
        expect(innerDiv.classes()).toContain('sm:justify-between');
    });

    it('should use ButtonDanger component', () => {
        const wrapper = mount(WorkflowError, {
            props: defaultProps,
        });

        const button = wrapper.findComponent(ButtonDanger);
        expect(button.exists()).toBe(true);
    });

    it('should have shrink-0 on button for responsive layout', () => {
        const wrapper = mount(WorkflowError, {
            props: defaultProps,
        });

        const button = wrapper.findComponent(ButtonDanger);
        expect(button.classes()).toContain('shrink-0');
    });

    it('should display custom error message', () => {
        const wrapper = mount(WorkflowError, {
            props: {
                errorMessage: 'Custom error message here',
            },
        });

        expect(wrapper.text()).toContain('Custom error message here');
    });

    it('should have proper icon sizes', () => {
        const wrapper = mount(WorkflowError, {
            props: defaultProps,
            global: {
                stubs: {
                    DynamicIcon: true,
                },
            },
        });

        const icons = wrapper.findAllComponents(DynamicIcon);
        expect(icons[0].classes()).toContain('size-5'); // Error icon
        expect(icons[1].classes()).toContain('size-4'); // Retry icon
    });
});
