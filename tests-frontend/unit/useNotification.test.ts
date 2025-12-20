import { useNotification } from '@/Composables/ui/useNotification';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

describe('useNotification', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        // Clear notifications before each test (global state)
        const { clear } = useNotification();
        clear();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('should initialise with empty notifications', () => {
        const { notifications } = useNotification();
        expect(notifications.value).toEqual([]);
    });

    it('should add a notification', () => {
        const { add, notifications } = useNotification();

        const id = add({
            message: 'Test notification',
            type: 'info',
            autoDismiss: false,
            dismissDelay: 3000,
        });

        expect(id).toBeTruthy();
        expect(notifications.value).toHaveLength(1);
        expect(notifications.value[0].message).toBe('Test notification');
        expect(notifications.value[0].type).toBe('info');
    });

    it('should add success notification', () => {
        const { success, notifications } = useNotification();

        success('Success message');

        expect(notifications.value).toHaveLength(1);
        expect(notifications.value[0].message).toBe('Success message');
        expect(notifications.value[0].type).toBe('success');
        expect(notifications.value[0].autoDismiss).toBe(true);
    });

    it('should add error notification', () => {
        const { error, notifications } = useNotification();

        error('Error message');

        expect(notifications.value).toHaveLength(1);
        expect(notifications.value[0].message).toBe('Error message');
        expect(notifications.value[0].type).toBe('error');
        expect(notifications.value[0].autoDismiss).toBe(true);
    });

    it('should add warning notification', () => {
        const { warning, notifications } = useNotification();

        warning('Warning message');

        expect(notifications.value).toHaveLength(1);
        expect(notifications.value[0].message).toBe('Warning message');
        expect(notifications.value[0].type).toBe('warning');
        expect(notifications.value[0].autoDismiss).toBe(true);
    });

    it('should add info notification', () => {
        const { info, notifications } = useNotification();

        info('Info message');

        expect(notifications.value).toHaveLength(1);
        expect(notifications.value[0].message).toBe('Info message');
        expect(notifications.value[0].type).toBe('info');
        expect(notifications.value[0].autoDismiss).toBe(true);
    });

    it('should remove notification by ID', () => {
        const { add, remove, notifications } = useNotification();

        const id = add({
            message: 'Test',
            type: 'info',
            autoDismiss: false,
            dismissDelay: 3000,
        });

        expect(notifications.value).toHaveLength(1);

        remove(id);

        expect(notifications.value).toHaveLength(0);
    });

    it('should not error when removing non-existent notification', () => {
        const { remove, notifications } = useNotification();

        expect(() => remove('non-existent-id')).not.toThrow();
        expect(notifications.value).toHaveLength(0);
    });

    it('should clear all notifications', () => {
        const { add, clear, notifications } = useNotification();

        add({
            message: 'Notification 1',
            type: 'info',
            autoDismiss: false,
            dismissDelay: 3000,
        });
        add({
            message: 'Notification 2',
            type: 'success',
            autoDismiss: false,
            dismissDelay: 3000,
        });

        expect(notifications.value).toHaveLength(2);

        clear();

        expect(notifications.value).toHaveLength(0);
    });

    it('should auto-dismiss notification after delay', () => {
        const { add, notifications } = useNotification();

        add({
            message: 'Auto-dismiss test',
            type: 'success',
            autoDismiss: true,
            dismissDelay: 3000,
        });

        expect(notifications.value).toHaveLength(1);

        vi.advanceTimersByTime(3000);

        expect(notifications.value).toHaveLength(0);
    });

    it('should use custom dismiss delay for success', () => {
        const { success, notifications } = useNotification();

        success('Test', 1000);

        expect(notifications.value).toHaveLength(1);
        expect(notifications.value[0].dismissDelay).toBe(1000);

        vi.advanceTimersByTime(1000);

        expect(notifications.value).toHaveLength(0);
    });

    it('should use default dismiss delay for error', () => {
        const { error, notifications } = useNotification();

        error('Error', true);

        expect(notifications.value).toHaveLength(1);
        expect(notifications.value[0].dismissDelay).toBe(5000);

        vi.advanceTimersByTime(5000);

        expect(notifications.value).toHaveLength(0);
    });

    it('should use custom dismiss delay for error', () => {
        const { error, notifications } = useNotification();

        error('Error', true, 2000);

        expect(notifications.value).toHaveLength(1);

        vi.advanceTimersByTime(2000);

        expect(notifications.value).toHaveLength(0);
    });

    it('should not auto-dismiss error when autoDismiss is false', () => {
        const { error, notifications } = useNotification();

        error('Error', false, 2000);

        expect(notifications.value).toHaveLength(1);

        vi.advanceTimersByTime(2000);

        expect(notifications.value).toHaveLength(1);
    });

    it('should use default dismiss delay for warning', () => {
        const { warning, notifications } = useNotification();

        warning('Warning');

        expect(notifications.value[0].dismissDelay).toBe(4000);
    });

    it('should use default dismiss delay for info', () => {
        const { info, notifications } = useNotification();

        info('Info');

        expect(notifications.value[0].dismissDelay).toBe(3000);
    });

    it('should generate unique IDs for notifications', () => {
        const { add } = useNotification();

        const id1 = add({
            message: 'Notification 1',
            type: 'info',
            autoDismiss: false,
            dismissDelay: 3000,
        });
        const id2 = add({
            message: 'Notification 2',
            type: 'info',
            autoDismiss: false,
            dismissDelay: 3000,
        });

        expect(id1).not.toBe(id2);
    });

    it('should handle multiple notifications', () => {
        const { success, error, warning, notifications } = useNotification();

        success('Success');
        error('Error');
        warning('Warning');

        expect(notifications.value).toHaveLength(3);
        expect(notifications.value[0].type).toBe('success');
        expect(notifications.value[1].type).toBe('error');
        expect(notifications.value[2].type).toBe('warning');
    });

    it('should add notification with custom dismiss delay option', () => {
        const { add, notifications } = useNotification();

        add(
            {
                message: 'Custom delay',
                type: 'info',
                autoDismiss: true,
                dismissDelay: 2000,
            },
            { autoDismissDelay: 1000 },
        );

        expect(notifications.value[0].dismissDelay).toBe(1000);

        vi.advanceTimersByTime(1000);

        expect(notifications.value).toHaveLength(0);
    });

    it('should compute notifications reactively', () => {
        const { add, notifications } = useNotification();

        expect(notifications.value).toHaveLength(0);

        add({
            message: 'Test',
            type: 'info',
            autoDismiss: false,
            dismissDelay: 3000,
        });

        expect(notifications.value).toHaveLength(1);
    });
});
