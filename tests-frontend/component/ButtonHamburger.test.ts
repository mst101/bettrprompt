import ButtonHamburger from '@/Components/Base/Button/ButtonHamburger.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('ButtonHamburger', () => {
    it('should render button element', () => {
        const wrapper = mount(ButtonHamburger, {
            props: {
                isOpen: false,
            },
        });

        expect(wrapper.find('button').exists()).toBe(true);
    });

    it('should have type button', () => {
        const wrapper = mount(ButtonHamburger, {
            props: {
                isOpen: false,
            },
        });

        expect(wrapper.find('button').attributes('type')).toBe('button');
    });

    it('should have correct aria-label', () => {
        const wrapper = mount(ButtonHamburger, {
            props: {
                isOpen: false,
            },
        });

        expect(wrapper.find('button').attributes('aria-label')).toBe(
            'Toggle navigation menu',
        );
    });

    it('should display bars-3 icon when closed', () => {
        const wrapper = mount(ButtonHamburger, {
            props: {
                isOpen: false,
            },
        });

        const icon = wrapper.findComponent(DynamicIcon);
        expect(icon.exists()).toBe(true);
        expect(icon.props('name')).toBe('bars-3');
    });

    it('should display x-mark icon when open', () => {
        const wrapper = mount(ButtonHamburger, {
            props: {
                isOpen: true,
            },
        });

        const icon = wrapper.findComponent(DynamicIcon);
        expect(icon.exists()).toBe(true);
        expect(icon.props('name')).toBe('x-mark');
    });

    it('should toggle icon when isOpen changes', async () => {
        const wrapper = mount(ButtonHamburger, {
            props: {
                isOpen: false,
            },
        });

        let icon = wrapper.findComponent(DynamicIcon);
        expect(icon.props('name')).toBe('bars-3');

        await wrapper.setProps({ isOpen: true });

        icon = wrapper.findComponent(DynamicIcon);
        expect(icon.props('name')).toBe('x-mark');
    });

    it('should have correct styling classes', () => {
        const wrapper = mount(ButtonHamburger, {
            props: {
                isOpen: false,
            },
        });

        const button = wrapper.find('button');
        expect(button.classes()).toContain('rounded-md');
        expect(button.classes()).toContain('cursor-pointer');
        expect(button.classes()).toContain('text-indigo-600');
        expect(button.classes()).toContain('p-2');
    });

    it('should have hover styling classes', () => {
        const wrapper = mount(ButtonHamburger, {
            props: {
                isOpen: false,
            },
        });

        const button = wrapper.find('button');
        expect(button.classes()).toContain('hover:bg-indigo-50');
        expect(button.classes()).toContain('hover:text-indigo-800');
    });

    it('should have active styling classes', () => {
        const wrapper = mount(ButtonHamburger, {
            props: {
                isOpen: false,
            },
        });

        const button = wrapper.find('button');
        expect(button.classes()).toContain('active:text-indigo-900');
    });

    it('should have focus ring classes', () => {
        const wrapper = mount(ButtonHamburger, {
            props: {
                isOpen: false,
            },
        });

        const button = wrapper.find('button');
        expect(button.classes()).toContain('focus:ring-2');
        expect(button.classes()).toContain('focus:ring-indigo-500');
        expect(button.classes()).toContain('focus:outline-hidden');
    });

    it('should have correct size classes', () => {
        const wrapper = mount(ButtonHamburger, {
            props: {
                isOpen: false,
            },
        });

        const button = wrapper.find('button');
        expect(button.classes()).toContain('size-10');
        expect(button.classes()).toContain('shrink-0');
    });

    it('should emit click events', async () => {
        const wrapper = mount(ButtonHamburger, {
            props: {
                isOpen: false,
            },
        });

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('click')).toBeTruthy();
        expect(wrapper.emitted('click')).toHaveLength(1);
    });

    it('should handle multiple clicks', async () => {
        const wrapper = mount(ButtonHamburger, {
            props: {
                isOpen: false,
            },
        });

        const button = wrapper.find('button');
        await button.trigger('click');
        await button.trigger('click');
        await button.trigger('click');

        expect(wrapper.emitted('click')).toHaveLength(3);
    });
});
