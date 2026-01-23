import Dropdown from '@/Components/Base/Dropdown.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('Dropdown', () => {
    it('should render trigger slot', () => {
        const wrapper = mount(Dropdown, {
            slots: {
                trigger: '<button>Toggle</button>',
                content: '<div>Content</div>',
            },
        });

        expect(wrapper.text()).toContain('Toggle');
    });

    it('should have role="button" on trigger', () => {
        const wrapper = mount(Dropdown, {
            slots: {
                trigger: '<button>Toggle</button>',
            },
        });

        const trigger = wrapper.find('[role="button"]');
        expect(trigger.exists()).toBe(true);
    });

    it('should have aria-haspopup="true" on trigger', () => {
        const wrapper = mount(Dropdown, {
            slots: {
                trigger: '<button>Toggle</button>',
            },
        });

        const trigger = wrapper.find('[role="button"]');
        expect(trigger.attributes('aria-haspopup')).toBe('true');
    });

    it('should have aria-expanded="false" initially', () => {
        const wrapper = mount(Dropdown, {
            slots: {
                trigger: '<button>Toggle</button>',
            },
        });

        const trigger = wrapper.find('[role="button"]');
        expect(trigger.attributes('aria-expanded')).toBe('false');
    });

    it('should have aria-expanded="true" when open', async () => {
        const wrapper = mount(Dropdown, {
            slots: {
                trigger: '<button>Toggle</button>',
            },
        });

        const trigger = wrapper.find('[role="button"]');
        await trigger.trigger('click');

        expect(trigger.attributes('aria-expanded')).toBe('true');
    });

    it('should have aria-expanded="false" after closing', async () => {
        const wrapper = mount(Dropdown, {
            slots: {
                trigger: '<button>Toggle</button>',
            },
        });

        const trigger = wrapper.find('[role="button"]');
        await trigger.trigger('click');
        await trigger.trigger('click');

        expect(trigger.attributes('aria-expanded')).toBe('false');
    });

    it('should have tabindex="0" on trigger for keyboard access', () => {
        const wrapper = mount(Dropdown, {
            slots: {
                trigger: '<button>Toggle</button>',
            },
        });

        const trigger = wrapper.find('[role="button"]');
        expect(trigger.attributes('tabindex')).toBe('0');
    });

    it('should toggle dropdown on Enter key', async () => {
        const wrapper = mount(Dropdown, {
            slots: {
                trigger: '<button>Toggle</button>',
            },
        });

        const trigger = wrapper.find('[role="button"]');
        await trigger.trigger('keydown.enter');

        expect(trigger.attributes('aria-expanded')).toBe('true');
    });

    it('should toggle dropdown on Space key', async () => {
        const wrapper = mount(Dropdown, {
            slots: {
                trigger: '<button>Toggle</button>',
            },
        });

        const trigger = wrapper.find('[role="button"]');
        await trigger.trigger('keydown.space');

        expect(trigger.attributes('aria-expanded')).toBe('true');
    });

    it('should render content slot when dropdown is open', async () => {
        const wrapper = mount(Dropdown, {
            slots: {
                trigger: '<button>Toggle</button>',
                content: '<div class="dropdown-item">Option 1</div>',
            },
        });

        const trigger = wrapper.find('[role="button"]');
        await trigger.trigger('click');

        const content = document.querySelector('.dropdown-item');
        expect(content).toBeTruthy();
        expect(content?.textContent).toBe('Option 1');
    });

    it('should apply custom content classes', async () => {
        const wrapper = mount(Dropdown, {
            props: {
                contentClasses: 'custom-class',
            },
            slots: {
                trigger: '<button>Toggle</button>',
                content: '<div>Content</div>',
            },
        });

        const trigger = wrapper.find('[role="button"]');
        await trigger.trigger('click');

        const content = document.querySelector('.custom-class');
        expect(content).toBeTruthy();
    });

    it('should support close method via expose', async () => {
        const wrapper = mount(Dropdown, {
            slots: {
                trigger: '<button>Toggle</button>',
            },
        });

        const trigger = wrapper.find('[role="button"]');
        await trigger.trigger('click');
        expect(trigger.attributes('aria-expanded')).toBe('true');

        // Call the exposed close method
        await (wrapper.vm as any).close();

        expect(trigger.attributes('aria-expanded')).toBe('false');
    });

    it('should have all accessibility attributes correctly set', () => {
        const wrapper = mount(Dropdown, {
            slots: {
                trigger: '<button>Toggle</button>',
            },
        });

        const trigger = wrapper.find('[role="button"]');

        expect(trigger.attributes('role')).toBe('button');
        expect(trigger.attributes('aria-haspopup')).toBe('true');
        expect(trigger.attributes('aria-expanded')).toBe('false');
        expect(trigger.attributes('tabindex')).toBe('0');
    });
});
