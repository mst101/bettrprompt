import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('ButtonSecondary', () => {
    it('should render button element', () => {
        const wrapper = mount(ButtonSecondary);

        expect(wrapper.find('button').exists()).toBe(true);
    });

    it('should render slot content', () => {
        const wrapper = mount(ButtonSecondary, {
            slots: {
                default: 'Cancel',
            },
        });

        expect(wrapper.text()).toBe('Cancel');
    });

    it('should render with secondary variant styling', () => {
        const wrapper = mount(ButtonSecondary);
        const button = wrapper.find('button');

        expect(button.classes()).toContain('inline-flex');
        expect(button.classes()).toContain('rounded-md');
        expect(button.classes()).toContain('bg-white');
        expect(button.classes()).toContain('border-gray-300');
        expect(button.classes()).toContain('text-gray-700');
    });

    it('should emit click events', async () => {
        const wrapper = mount(ButtonSecondary);

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('click')).toBeTruthy();
        expect(wrapper.emitted('click')).toHaveLength(1);
    });

    it('should support disabled prop', () => {
        const wrapper = mount(ButtonSecondary, {
            props: {
                disabled: true,
            },
        });

        const button = wrapper.find('button');
        expect(button.attributes('disabled')).toBeDefined();
    });

    it('should support type prop', () => {
        const wrapper = mount(ButtonSecondary, {
            props: {
                type: 'submit',
            },
        });

        expect(wrapper.find('button').attributes('type')).toBe('submit');
    });

    it('should have default type of button', () => {
        const wrapper = mount(ButtonSecondary);

        expect(wrapper.find('button').attributes('type')).toBe('button');
    });

    it('should accept reset type', () => {
        const wrapper = mount(ButtonSecondary, {
            props: {
                type: 'reset',
            },
        });

        expect(wrapper.find('button').attributes('type')).toBe('reset');
    });
});
