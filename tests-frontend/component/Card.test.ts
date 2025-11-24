import Card from '@/Components/Card.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('Card', () => {
    it('should render with default medium padding', () => {
        const wrapper = mount(Card);
        const card = wrapper.find('div');

        expect(card.exists()).toBe(true);
        expect(card.classes()).toContain('p-6');
        expect(card.classes()).toContain('bg-white');
        expect(card.classes()).toContain('shadow-lg');
    });

    it('should render with no padding', () => {
        const wrapper = mount(Card, {
            props: { padding: 'none' },
        });
        const card = wrapper.find('div');

        expect(card.classes()).not.toContain('p-4');
        expect(card.classes()).not.toContain('p-6');
        expect(card.classes()).not.toContain('p-8');
    });

    it('should render with small padding', () => {
        const wrapper = mount(Card, {
            props: { padding: 'small' },
        });
        const card = wrapper.find('div');

        expect(card.classes()).toContain('p-4');
    });

    it('should render with large padding', () => {
        const wrapper = mount(Card, {
            props: { padding: 'large' },
        });
        const card = wrapper.find('div');

        expect(card.classes()).toContain('p-8');
    });

    it('should render slot content', () => {
        const wrapper = mount(Card, {
            slots: {
                default: '<p>Card content</p>',
            },
        });

        expect(wrapper.html()).toContain('Card content');
    });

    it('should have rounded-sm corners on larger screens', () => {
        const wrapper = mount(Card);
        const card = wrapper.find('div');

        expect(card.classes()).toContain('sm:rounded-lg');
    });

    it('should have overflow hidden', () => {
        const wrapper = mount(Card);
        const card = wrapper.find('div');

        expect(card.classes()).toContain('overflow-hidden');
    });
});
