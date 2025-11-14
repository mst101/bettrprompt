import LinkButton from '@/Components/LinkButton.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('LinkButton', () => {
    const defaultProps = {
        href: '/test-page',
    };

    it('should render Inertia Link component', () => {
        const wrapper = mount(LinkButton, {
            props: defaultProps,
        });

        expect(wrapper.find('a').exists()).toBe(true);
    });

    it('should render slot content', () => {
        const wrapper = mount(LinkButton, {
            props: defaultProps,
            slots: {
                default: 'Click here',
            },
        });

        expect(wrapper.text()).toBe('Click here');
    });

    it('should have correct href attribute', () => {
        const wrapper = mount(LinkButton, {
            props: {
                href: '/my-page',
            },
        });

        expect(wrapper.find('a').attributes('href')).toBe('/my-page');
    });

    it('should support optional id prop', () => {
        const wrapper = mount(LinkButton, {
            props: {
                href: '/test',
                id: 'my-link',
            },
        });

        expect(wrapper.find('a').attributes('id')).toBe('my-link');
    });

    it('should render default variant by default', () => {
        const wrapper = mount(LinkButton, {
            props: defaultProps,
        });

        const link = wrapper.find('a');
        expect(link.classes()).toContain('border-gray-300');
        expect(link.classes()).toContain('bg-white');
        expect(link.classes()).toContain('text-gray-500');
    });

    it('should render default variant with rounded-md', () => {
        const wrapper = mount(LinkButton, {
            props: {
                ...defaultProps,
                variant: 'default',
            },
        });

        const link = wrapper.find('a');
        expect(link.classes()).toContain('rounded-md');
    });

    it('should render primary variant', () => {
        const wrapper = mount(LinkButton, {
            props: {
                ...defaultProps,
                variant: 'primary',
            },
        });

        const link = wrapper.find('a');
        expect(link.classes()).toContain('bg-indigo-600');
        expect(link.classes()).toContain('text-white');
        expect(link.classes()).toContain('rounded-md');
    });

    it('should render rounded-left variant', () => {
        const wrapper = mount(LinkButton, {
            props: {
                ...defaultProps,
                variant: 'rounded-left',
            },
        });

        const link = wrapper.find('a');
        expect(link.classes()).toContain('rounded-l-md');
        expect(link.classes()).not.toContain('rounded-md');
    });

    it('should render rounded-right variant', () => {
        const wrapper = mount(LinkButton, {
            props: {
                ...defaultProps,
                variant: 'rounded-right',
            },
        });

        const link = wrapper.find('a');
        expect(link.classes()).toContain('rounded-r-md');
        expect(link.classes()).not.toContain('rounded-md');
    });

    it('should have base styling classes for default variant', () => {
        const wrapper = mount(LinkButton, {
            props: defaultProps,
        });

        const link = wrapper.find('a');
        expect(link.classes()).toContain('inline-flex');
        expect(link.classes()).toContain('items-center');
        expect(link.classes()).toContain('border');
        expect(link.classes()).toContain('px-4');
        expect(link.classes()).toContain('py-2');
        expect(link.classes()).toContain('text-xs');
        expect(link.classes()).toContain('font-medium');
    });

    it('should have base styling classes for primary variant', () => {
        const wrapper = mount(LinkButton, {
            props: {
                ...defaultProps,
                variant: 'primary',
            },
        });

        const link = wrapper.find('a');
        expect(link.classes()).toContain('inline-flex');
        expect(link.classes()).toContain('items-center');
        expect(link.classes()).toContain('px-4');
        expect(link.classes()).toContain('py-2');
        expect(link.classes()).toContain('text-xs');
        expect(link.classes()).toContain('font-medium');
    });

    it('should have hover styling for default variant', () => {
        const wrapper = mount(LinkButton, {
            props: defaultProps,
        });

        const link = wrapper.find('a');
        expect(link.classes()).toContain('hover:bg-gray-50');
    });

    it('should have hover styling for primary variant', () => {
        const wrapper = mount(LinkButton, {
            props: {
                ...defaultProps,
                variant: 'primary',
            },
        });

        const link = wrapper.find('a');
        expect(link.classes()).toContain('hover:bg-indigo-700');
    });

    it('should have focus ring classes', () => {
        const wrapper = mount(LinkButton, {
            props: defaultProps,
        });

        const link = wrapper.find('a');
        expect(link.classes()).toContain('focus:ring-2');
        expect(link.classes()).toContain('focus:ring-indigo-500');
        expect(link.classes()).toContain('focus:outline-hidden');
    });

    it('should have focus ring offset for primary variant', () => {
        const wrapper = mount(LinkButton, {
            props: {
                ...defaultProps,
                variant: 'primary',
            },
        });

        const link = wrapper.find('a');
        expect(link.classes()).toContain('focus:ring-offset-2');
        expect(link.classes()).toContain('focus:ring-offset-gray-100');
    });

    it('should have shadow for primary variant', () => {
        const wrapper = mount(LinkButton, {
            props: {
                ...defaultProps,
                variant: 'primary',
            },
        });

        const link = wrapper.find('a');
        expect(link.classes()).toContain('shadow-xs');
    });
});
