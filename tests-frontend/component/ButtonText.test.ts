import ButtonText from '@/Components/Base/Button/ButtonText.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('ButtonText', () => {
    it('should render button element', () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
            },
        });

        expect(wrapper.find('button').exists()).toBe(true);
    });

    it('should render slot content', () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
            },
            slots: {
                default: 'Click me',
            },
        });

        expect(wrapper.text()).toBe('Click me');
    });

    it('should have correct id attribute', () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'my-button',
            },
        });

        expect(wrapper.find('button').attributes('id')).toBe('my-button');
    });

    it('should render primary variant by default', () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
            },
        });

        const button = wrapper.find('button');
        expect(button.classes()).toContain('text-indigo-600');
    });

    it('should render primary variant when explicitly set', () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
                variant: 'primary',
            },
        });

        const button = wrapper.find('button');
        expect(button.classes()).toContain('text-indigo-600');
        expect(button.classes()).toContain('hover:text-indigo-800');
    });

    it('should render secondary variant', () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
                variant: 'secondary',
            },
        });

        const button = wrapper.find('button');
        expect(button.classes()).toContain('text-gray-600');
        expect(button.classes()).toContain('hover:text-gray-800');
    });

    it('should render danger variant', () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
                variant: 'danger',
            },
        });

        const button = wrapper.find('button');
        expect(button.classes()).toContain('text-red-600');
        expect(button.classes()).toContain('hover:text-red-800');
    });

    it('should have default type of button', () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
            },
        });

        expect(wrapper.find('button').attributes('type')).toBe('button');
    });

    it('should accept submit type', () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
                type: 'submit',
            },
        });

        expect(wrapper.find('button').attributes('type')).toBe('submit');
    });

    it('should accept reset type', () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
                type: 'reset',
            },
        });

        expect(wrapper.find('button').attributes('type')).toBe('reset');
    });

    it('should not be disabled by default', () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
            },
        });

        expect(wrapper.find('button').attributes('disabled')).toBeUndefined();
    });

    it('should support disabled prop', () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
                disabled: true,
            },
        });

        const button = wrapper.find('button');
        expect(button.attributes('disabled')).toBeDefined();
        expect(button.classes()).toContain('disabled:cursor-not-allowed');
        expect(button.classes()).toContain('disabled:opacity-50');
    });

    it('should have base styling classes', () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
            },
        });

        const button = wrapper.find('button');
        expect(button.classes()).toContain('cursor-pointer');
        expect(button.classes()).toContain('text-sm');
        expect(button.classes()).toContain('font-normal');
        expect(button.classes()).toContain('rounded-md');
    });

    it('should have focus ring classes', () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
            },
        });

        const button = wrapper.find('button');
        expect(button.classes()).toContain('focus:ring-2');
        expect(button.classes()).toContain('focus:outline-hidden');
    });

    it('should not have underline by default', () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
            },
        });

        const button = wrapper.find('button');
        expect(button.classes()).not.toContain('hover:underline');
    });

    it('should apply underline when prop is true', () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
                underline: true,
            },
        });

        const button = wrapper.find('button');
        expect(button.classes()).toContain('hover:underline');
    });

    it('should emit click events', async () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
            },
        });

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('click')).toBeTruthy();
        expect(wrapper.emitted('click')).toHaveLength(1);
    });

    it('should handle multiple clicks', async () => {
        const wrapper = mount(ButtonText, {
            props: {
                id: 'test-button',
            },
        });

        const button = wrapper.find('button');
        await button.trigger('click');
        await button.trigger('click');
        await button.trigger('click');

        expect(wrapper.emitted('click')).toHaveLength(3);
    });
});
