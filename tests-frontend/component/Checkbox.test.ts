import Checkbox from '@/Components/Base/Checkbox.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('Checkbox', () => {
    it('should render checkbox input', () => {
        const wrapper = mount(Checkbox, {
            props: {
                checked: false,
            },
        });

        const input = wrapper.find('input[type="checkbox"]');
        expect(input.exists()).toBe(true);
    });

    it('should be unchecked by default', () => {
        const wrapper = mount(Checkbox, {
            props: {
                checked: false,
            },
        });

        const input = wrapper.find('input[type="checkbox"]')
            .element as HTMLInputElement;
        expect(input.checked).toBe(false);
    });

    it('should be checked when prop is true', () => {
        const wrapper = mount(Checkbox, {
            props: {
                checked: true,
            },
        });

        const input = wrapper.find('input[type="checkbox"]')
            .element as HTMLInputElement;
        expect(input.checked).toBe(true);
    });

    it('should emit update:checked when clicked', async () => {
        const wrapper = mount(Checkbox, {
            props: {
                checked: false,
            },
        });

        const input = wrapper.find('input[type="checkbox"]');
        await input.setValue(true);

        expect(wrapper.emitted('update:checked')).toBeTruthy();
        expect(wrapper.emitted('update:checked')?.[0]).toEqual([true]);
    });

    it('should toggle from checked to unchecked', async () => {
        const wrapper = mount(Checkbox, {
            props: {
                checked: true,
            },
        });

        const input = wrapper.find('input[type="checkbox"]');
        await input.setValue(false);

        expect(wrapper.emitted('update:checked')?.[0]).toEqual([false]);
    });

    it('should have correct styling classes', () => {
        const wrapper = mount(Checkbox, {
            props: {
                checked: false,
            },
        });

        const input = wrapper.find('input[type="checkbox"]');
        expect(input.classes()).toContain('rounded-sm');
        expect(input.classes()).toContain('border-indigo-100');
        expect(input.classes()).toContain('text-indigo-600');
    });

    it('should accept value prop', () => {
        const wrapper = mount(Checkbox, {
            props: {
                checked: false,
                value: 'option1',
            },
        });

        const input = wrapper.find('input[type="checkbox"]')
            .element as HTMLInputElement;
        expect(input.value).toBe('option1');
    });

    it('should work without value prop', () => {
        const wrapper = mount(Checkbox, {
            props: {
                checked: false,
            },
        });

        expect(wrapper.find('input[type="checkbox"]').exists()).toBe(true);
    });
});
