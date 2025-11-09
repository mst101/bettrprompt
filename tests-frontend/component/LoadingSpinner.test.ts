import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

// Mock DynamicIcon component to avoid complex icon loading in tests
const DynamicIconStub = {
    name: 'DynamicIcon',
    props: ['name'],
    template:
        '<svg data-testid="icon" :data-icon-name="name"><title>Icon</title></svg>',
};

describe('LoadingSpinner', () => {
    it('should render with default medium size', () => {
        const wrapper = mount(LoadingSpinner, {
            global: {
                stubs: {
                    DynamicIcon: DynamicIconStub,
                },
            },
        });

        const icon = wrapper.find('[data-testid="icon"]');
        expect(icon.exists()).toBe(true);
        expect(icon.attributes('data-icon-name')).toBe('spinner');
        expect(icon.classes()).toContain('h-5');
        expect(icon.classes()).toContain('w-5');
    });

    it('should render with small size', () => {
        const wrapper = mount(LoadingSpinner, {
            props: {
                size: 'small',
            },
            global: {
                stubs: {
                    DynamicIcon: DynamicIconStub,
                },
            },
        });

        const icon = wrapper.find('[data-testid="icon"]');
        expect(icon.classes()).toContain('h-4');
        expect(icon.classes()).toContain('w-4');
    });

    it('should render with medium size', () => {
        const wrapper = mount(LoadingSpinner, {
            props: {
                size: 'medium',
            },
            global: {
                stubs: {
                    DynamicIcon: DynamicIconStub,
                },
            },
        });

        const icon = wrapper.find('[data-testid="icon"]');
        expect(icon.classes()).toContain('h-5');
        expect(icon.classes()).toContain('w-5');
    });

    it('should render with large size', () => {
        const wrapper = mount(LoadingSpinner, {
            props: {
                size: 'large',
            },
            global: {
                stubs: {
                    DynamicIcon: DynamicIconStub,
                },
            },
        });

        const icon = wrapper.find('[data-testid="icon"]');
        expect(icon.classes()).toContain('h-8');
        expect(icon.classes()).toContain('w-8');
    });

    it('should pass correct icon name to DynamicIcon', () => {
        const wrapper = mount(LoadingSpinner, {
            global: {
                stubs: {
                    DynamicIcon: DynamicIconStub,
                },
            },
        });

        const icon = wrapper.find('[data-testid="icon"]');
        expect(icon.attributes('data-icon-name')).toBe('spinner');
    });

    it('should have correct CSS classes', () => {
        const wrapper = mount(LoadingSpinner, {
            global: {
                stubs: {
                    DynamicIcon: DynamicIconStub,
                },
            },
        });

        const icon = wrapper.find('[data-testid="icon"]');
        expect(icon.classes()).toContain('text-indigo-600');
    });
});
