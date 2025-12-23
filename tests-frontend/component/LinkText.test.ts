import LinkText from '@/Components/Base/LinkText.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('LinkText', () => {
    const defaultProps = {
        href: '/test-page',
    };

    it('should render Inertia Link component', () => {
        const wrapper = mount(LinkText, {
            props: defaultProps,
        });

        expect(wrapper.find('a').exists()).toBe(true);
    });

    it('should render slot content', () => {
        const wrapper = mount(LinkText, {
            props: defaultProps,
            slots: {
                default: 'Click here',
            },
        });

        expect(wrapper.text()).toBe('Click here');
    });

    it('should have correct href attribute', () => {
        const wrapper = mount(LinkText, {
            props: {
                href: '/my-page',
            },
        });

        expect(wrapper.find('a').attributes('href')).toBe('/my-page');
    });

    it('should have correct styling classes', () => {
        const wrapper = mount(LinkText, {
            props: defaultProps,
        });

        const link = wrapper.find('a');
        expect(link.classes()).toContain('rounded-md');
        expect(link.classes()).toContain('p-1');
        expect(link.classes()).toContain('text-indigo-600');
    });

    it('should have hover styling', () => {
        const wrapper = mount(LinkText, {
            props: defaultProps,
        });

        const link = wrapper.find('a');
        expect(link.classes()).toContain('hover:text-indigo-700');
    });

    it('should have transition classes', () => {
        const wrapper = mount(LinkText, {
            props: defaultProps,
        });

        const link = wrapper.find('a');
        expect(link.classes()).toContain('transition');
    });

    it('should have focus ring classes', () => {
        const wrapper = mount(LinkText, {
            props: defaultProps,
        });

        const link = wrapper.find('a');
        expect(link.classes()).toContain('focus:ring-2');
        expect(link.classes()).toContain('focus:ring-indigo-500');
        expect(link.classes()).toContain('focus:ring-offset-indigo-100');
        expect(link.classes()).toContain('focus:outline-hidden');
    });

    it('should render multiple links independently', () => {
        const wrapper1 = mount(LinkText, {
            props: {
                href: '/page-1',
            },
            slots: {
                default: 'Link 1',
            },
        });

        const wrapper2 = mount(LinkText, {
            props: {
                href: '/page-2',
            },
            slots: {
                default: 'Link 2',
            },
        });

        expect(wrapper1.text()).toBe('Link 1');
        expect(wrapper2.text()).toBe('Link 2');
        expect(wrapper1.find('a').attributes('href')).toBe('/page-1');
        expect(wrapper2.find('a').attributes('href')).toBe('/page-2');
    });

    it('should support complex slot content', () => {
        const wrapper = mount(LinkText, {
            props: defaultProps,
            slots: {
                default: '<strong>Bold Link</strong>',
            },
        });

        expect(wrapper.html()).toContain('<strong>Bold Link</strong>');
    });
});
