import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import InputError from '@/Components/InputError.vue';

describe('InputError', () => {
    it('should render error message', () => {
        const wrapper = mount(InputError, {
            props: {
                message: 'This field is required',
            },
        });

        expect(wrapper.text()).toBe('This field is required');
    });

    it('should not be visible when no message', () => {
        const wrapper = mount(InputError, {
            props: {
                message: undefined,
            },
        });

        const p = wrapper.find('p');
        expect(p.text()).toBe('');
    });

    it('should have correct styling classes', () => {
        const wrapper = mount(InputError, {
            props: {
                message: 'Error',
            },
        });

        const p = wrapper.find('p');
        expect(p.classes()).toContain('text-sm');
        expect(p.classes()).toContain('text-red-600');
    });

    it('should show when message is provided', () => {
        const wrapper = mount(InputError, {
            props: {
                message: 'Invalid email format',
            },
        });

        expect(wrapper.find('div').isVisible()).toBe(true);
    });

    it('should hide when message is empty string', () => {
        const wrapper = mount(InputError, {
            props: {
                message: '',
            },
        });

        const p = wrapper.find('p');
        expect(p.text()).toBe('');
    });

    it('should update visibility when message prop changes', async () => {
        const wrapper = mount(InputError, {
            props: {
                message: 'Error',
            },
        });

        expect(wrapper.text()).toBe('Error');

        await wrapper.setProps({ message: '' });
        expect(wrapper.find('p').text()).toBe('');

        await wrapper.setProps({ message: 'New error' });
        expect(wrapper.text()).toBe('New error');
    });
});
