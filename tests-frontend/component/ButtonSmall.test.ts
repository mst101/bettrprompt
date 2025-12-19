import ButtonSmall from '@/Components/Base/Button/ButtonSmall.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('ButtonSmall', () => {
    it('should render button element', () => {
        const wrapper = mount(ButtonSmall);

        expect(wrapper.find('button').exists()).toBe(true);
    });

    it('should render slot content', () => {
        const wrapper = mount(ButtonSmall, {
            slots: {
                default: 'Click me',
            },
        });

        expect(wrapper.text()).toBe('Click me');
    });

    it('should have default type of button', () => {
        const wrapper = mount(ButtonSmall);

        expect(wrapper.find('button').attributes('type')).toBe('button');
    });

    it('should support custom type prop', () => {
        const wrapper = mount(ButtonSmall, {
            props: {
                type: 'submit',
            },
        });

        expect(wrapper.find('button').attributes('type')).toBe('submit');
    });

    it('should support id prop', () => {
        const wrapper = mount(ButtonSmall, {
            props: {
                id: 'my-button',
            },
        });

        expect(wrapper.find('button').attributes('id')).toBe('my-button');
    });

    it('should support title prop', () => {
        const wrapper = mount(ButtonSmall, {
            props: {
                title: 'Click to expand',
            },
        });

        expect(wrapper.find('button').attributes('title')).toBe(
            'Click to expand',
        );
    });

    it('should be enabled by default', () => {
        const wrapper = mount(ButtonSmall);

        expect(wrapper.find('button').attributes('disabled')).toBeUndefined();
    });

    it('should support disabled prop', () => {
        const wrapper = mount(ButtonSmall, {
            props: {
                disabled: true,
            },
        });

        expect(wrapper.find('button').attributes('disabled')).toBeDefined();
    });

    it('should have base styling classes', () => {
        const wrapper = mount(ButtonSmall);

        const button = wrapper.find('button');
        expect(button.classes()).toContain('rounded');
        expect(button.classes()).toContain('bg-indigo-200');
        expect(button.classes()).toContain('px-2');
        expect(button.classes()).toContain('py-1');
        expect(button.classes()).toContain('text-xs');
        expect(button.classes()).toContain('font-medium');
        expect(button.classes()).toContain('text-indigo-700');
    });

    it('should have hover styling', () => {
        const wrapper = mount(ButtonSmall);

        const button = wrapper.find('button');
        expect(button.classes()).toContain('hover:bg-indigo-300');
    });

    it('should have focus ring classes', () => {
        const wrapper = mount(ButtonSmall);

        const button = wrapper.find('button');
        expect(button.classes()).toContain('focus:ring-2');
        expect(button.classes()).toContain('focus:ring-indigo-500');
        expect(button.classes()).toContain('focus:ring-offset-1');
        expect(button.classes()).toContain('focus:outline-none');
    });

    it('should have disabled styling classes', () => {
        const wrapper = mount(ButtonSmall);

        const button = wrapper.find('button');
        expect(button.classes()).toContain('disabled:cursor-not-allowed');
        expect(button.classes()).toContain('disabled:opacity-50');
    });

    it('should have transition classes', () => {
        const wrapper = mount(ButtonSmall);

        const button = wrapper.find('button');
        expect(button.classes()).toContain('transition-colors');
    });

    it('should emit click event', async () => {
        const wrapper = mount(ButtonSmall);

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('click')).toBeTruthy();
    });
});
