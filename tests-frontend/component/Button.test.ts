import Button from '@/Components/Button.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('Button', () => {
    describe('Rendering', () => {
        it('should render button element', () => {
            const wrapper = mount(Button);

            expect(wrapper.find('button').exists()).toBe(true);
        });

        it('should render slot content', () => {
            const wrapper = mount(Button, {
                slots: {
                    default: 'Click me',
                },
            });

            expect(wrapper.text()).toBe('Click me');
        });
    });

    describe('Variants', () => {
        it('should render primary variant by default', () => {
            const wrapper = mount(Button);
            const button = wrapper.find('button');

            expect(button.classes()).toContain('bg-indigo-600');
            expect(button.classes()).toContain('text-white');
            expect(button.classes()).toContain('border-transparent');
        });

        it('should render primary variant when explicitly set', () => {
            const wrapper = mount(Button, {
                props: {
                    variant: 'primary',
                },
            });
            const button = wrapper.find('button');

            expect(button.classes()).toContain('bg-indigo-600');
            expect(button.classes()).toContain('text-white');
        });

        it('should render secondary variant', () => {
            const wrapper = mount(Button, {
                props: {
                    variant: 'secondary',
                },
            });
            const button = wrapper.find('button');

            expect(button.classes()).toContain('bg-white');
            expect(button.classes()).toContain('text-gray-700');
            expect(button.classes()).toContain('border-gray-300');
        });

        it('should render danger variant', () => {
            const wrapper = mount(Button, {
                props: {
                    variant: 'danger',
                },
            });
            const button = wrapper.find('button');

            expect(button.classes()).toContain('bg-red-600');
            expect(button.classes()).toContain('text-white');
        });
    });

    describe('Sizes', () => {
        it('should render medium size by default', () => {
            const wrapper = mount(Button);
            const button = wrapper.find('button');

            expect(button.classes()).toContain('px-4');
            expect(button.classes()).toContain('py-2');
            expect(button.classes()).toContain('text-sm');
        });

        it('should render small size', () => {
            const wrapper = mount(Button, {
                props: {
                    size: 'sm',
                },
            });
            const button = wrapper.find('button');

            expect(button.classes()).toContain('px-3');
            expect(button.classes()).toContain('py-1.5');
            expect(button.classes()).toContain('text-sm');
        });

        it('should render medium size', () => {
            const wrapper = mount(Button, {
                props: {
                    size: 'md',
                },
            });
            const button = wrapper.find('button');

            expect(button.classes()).toContain('px-4');
            expect(button.classes()).toContain('py-2');
            expect(button.classes()).toContain('text-sm');
        });

        it('should render large size', () => {
            const wrapper = mount(Button, {
                props: {
                    size: 'lg',
                },
            });
            const button = wrapper.find('button');

            expect(button.classes()).toContain('px-6');
            expect(button.classes()).toContain('py-3');
            expect(button.classes()).toContain('text-sm');
        });
    });

    describe('Common Styling', () => {
        it('should have base styling classes', () => {
            const wrapper = mount(Button);
            const button = wrapper.find('button');

            expect(button.classes()).toContain('inline-flex');
            expect(button.classes()).toContain('items-center');
            expect(button.classes()).toContain('justify-center');
            expect(button.classes()).toContain('rounded-md');
            expect(button.classes()).toContain('font-medium');
        });

        it('should have focus ring classes', () => {
            const wrapper = mount(Button);
            const button = wrapper.find('button');

            expect(button.classes()).toContain('focus:ring-2');
            expect(button.classes()).toContain('focus:ring-offset-2');
            expect(button.classes()).toContain('focus:outline-hidden');
        });
    });

    describe('Type Attribute', () => {
        it('should have default type of button', () => {
            const wrapper = mount(Button);

            expect(wrapper.find('button').attributes('type')).toBe('button');
        });

        it('should accept submit type', () => {
            const wrapper = mount(Button, {
                props: {
                    type: 'submit',
                },
            });

            expect(wrapper.find('button').attributes('type')).toBe('submit');
        });

        it('should accept reset type', () => {
            const wrapper = mount(Button, {
                props: {
                    type: 'reset',
                },
            });

            expect(wrapper.find('button').attributes('type')).toBe('reset');
        });
    });

    describe('Disabled State', () => {
        it('should not be disabled by default', () => {
            const wrapper = mount(Button);

            expect(
                wrapper.find('button').attributes('disabled'),
            ).toBeUndefined();
        });

        it('should support disabled prop', () => {
            const wrapper = mount(Button, {
                props: {
                    disabled: true,
                },
            });
            const button = wrapper.find('button');

            expect(button.attributes('disabled')).toBeDefined();
            expect(button.classes()).toContain('disabled:cursor-not-allowed');
            expect(button.classes()).toContain('disabled:opacity-50');
        });

        it('should be disabled when loading', () => {
            const wrapper = mount(Button, {
                props: {
                    loading: true,
                },
            });

            expect(wrapper.find('button').attributes('disabled')).toBeDefined();
        });

        it('should be disabled when both disabled and loading are true', () => {
            const wrapper = mount(Button, {
                props: {
                    disabled: true,
                    loading: true,
                },
            });

            expect(wrapper.find('button').attributes('disabled')).toBeDefined();
        });
    });

    describe('Loading State', () => {
        it('should not show loading spinner by default', () => {
            const wrapper = mount(Button);

            expect(
                wrapper.findComponent({ name: 'DynamicIcon' }).exists(),
            ).toBe(false);
        });

        it('should show loading spinner when loading prop is true', () => {
            const wrapper = mount(Button, {
                props: {
                    loading: true,
                },
            });

            const icon = wrapper.findComponent({ name: 'DynamicIcon' });
            expect(icon.exists()).toBe(true);
            expect(icon.props('name')).toBe('arrow-path-spin');
        });

        it('should have animate-spin class on spinner', () => {
            const wrapper = mount(Button, {
                props: {
                    loading: true,
                },
            });

            // Check that the button's HTML contains the animate-spin class
            // (DynamicIcon forwards it to the SVG via v-bind="$attrs")
            expect(wrapper.html()).toContain('animate-spin');
        });

        it('should render slot content alongside spinner when loading', () => {
            const wrapper = mount(Button, {
                props: {
                    loading: true,
                },
                slots: {
                    default: 'Saving...',
                },
            });

            expect(
                wrapper.findComponent({ name: 'DynamicIcon' }).exists(),
            ).toBe(true);
            expect(wrapper.text()).toBe('Saving...');
        });
    });

    describe('Click Events', () => {
        it('should emit click events', async () => {
            const wrapper = mount(Button);

            await wrapper.find('button').trigger('click');

            expect(wrapper.emitted('click')).toBeTruthy();
            expect(wrapper.emitted('click')).toHaveLength(1);
        });

        it('should handle multiple clicks', async () => {
            const wrapper = mount(Button);
            const button = wrapper.find('button');

            await button.trigger('click');
            await button.trigger('click');
            await button.trigger('click');

            expect(wrapper.emitted('click')).toHaveLength(3);
        });
    });
});
