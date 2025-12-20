import InputLabel from '@/Components/Base/InputLabel.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('InputLabel', () => {
    it('should render label element', () => {
        const wrapper = mount(InputLabel);

        expect(wrapper.find('label').exists()).toBe(true);
    });

    it('should display value prop', () => {
        const wrapper = mount(InputLabel, {
            props: {
                value: 'Email Address',
            },
        });

        expect(wrapper.text()).toBe('Email Address');
    });

    it('should display slot content when no value prop', () => {
        const wrapper = mount(InputLabel, {
            slots: {
                default: 'Username',
            },
        });

        expect(wrapper.text()).toBe('Username');
    });

    it('should prefer value prop over slot', () => {
        const wrapper = mount(InputLabel, {
            props: {
                value: 'From Value',
            },
            slots: {
                default: 'From Slot',
            },
        });

        expect(wrapper.text()).toBe('From Value');
    });

    it('should have correct styling classes', () => {
        const wrapper = mount(InputLabel);
        const label = wrapper.find('label');

        expect(label.classes()).toContain('block');
        expect(label.classes()).toContain('text-sm');
        expect(label.classes()).toContain('font-medium');
        expect(label.classes()).toContain('text-indigo-700');
    });

    it('should render empty when no value or slot', () => {
        const wrapper = mount(InputLabel);

        expect(wrapper.text()).toBe('');
    });
});
