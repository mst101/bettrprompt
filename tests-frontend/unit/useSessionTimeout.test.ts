import { useSessionTimeout } from '@/Composables/features/useSessionTimeout';
import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';

// Mock Inertia router
vi.mock('@inertiajs/vue3', () => ({
    router: {
        post: vi.fn(),
    },
}));

// Mock useNotification
const mockWarning = vi.fn();
const mockError = vi.fn();

vi.mock('@/Composables/ui/useNotification', () => ({
    useNotification: () => ({
        warning: mockWarning,
        error: mockError,
    }),
}));

describe('useSessionTimeout', () => {
    beforeEach(() => {
        // Enable fake timers
        vi.useFakeTimers();

        // Clear all mocks
        vi.clearAllMocks();
        mockWarning.mockClear();
        mockError.mockClear();

        // Mock window.location.href
        delete (window as any).location;
        window.location = { href: '' } as any;
    });

    afterEach(() => {
        // Restore real timers
        vi.useRealTimers();
    });

    /**
     * Helper to mount a test component that uses the composable
     */
    function createTestComponent() {
        return defineComponent({
            setup() {
                useSessionTimeout();
                return () => h('div');
            },
        });
    }

    it('should show warning 5 minutes before session expiry', () => {
        mount(createTestComponent());

        // Fast-forward to 1 hour 55 minutes (115 minutes)
        const WARNING_TIME = 115 * 60 * 1000;
        vi.advanceTimersByTime(WARNING_TIME);

        // Warning should have been shown
        expect(mockWarning).toHaveBeenCalledWith(
            expect.stringContaining('Your session will expire in 5 minutes'),
            10000,
        );
    });

    it('should perform automatic logout after 2 hours', () => {
        const mockPost = vi.fn();
        vi.mocked(router.post).mockImplementation(mockPost as any);

        mount(createTestComponent());

        // Fast-forward to exactly 2 hours
        const SESSION_LIFETIME = 120 * 60 * 1000;
        vi.advanceTimersByTime(SESSION_LIFETIME);

        // Should show logout warning
        expect(mockWarning).toHaveBeenCalledWith(
            expect.stringContaining('Your session has expired'),
            false,
        );

        // Fast-forward the logout delay (1.5 seconds)
        vi.advanceTimersByTime(1500);

        // Should have called router.post for logout
        expect(mockPost).toHaveBeenCalledWith(
            '/logout',
            {},
            expect.objectContaining({
                onFinish: expect.any(Function),
            }),
        );
    });

    it('should reset timeout on user activity', () => {
        mount(createTestComponent());

        // Fast-forward to 1 hour 50 minutes (close to warning time)
        vi.advanceTimersByTime(110 * 60 * 1000);

        // Simulate user activity (mousedown)
        window.dispatchEvent(new Event('mousedown'));

        // Fast-forward another 1 hour 50 minutes (should not warn yet since we reset)
        vi.advanceTimersByTime(110 * 60 * 1000);

        // Should not have shown warning yet (we reset the timer with activity)
        expect(mockWarning).not.toHaveBeenCalled();

        // Now fast-forward to warning time from the reset point (5 minutes)
        vi.advanceTimersByTime(5 * 60 * 1000);

        // Now it should warn
        expect(mockWarning).toHaveBeenCalledWith(
            expect.stringContaining('Your session will expire in 5 minutes'),
            10000,
        );
    });

    it('should track multiple activity event types', () => {
        const activityEvents = ['mousedown', 'keydown', 'scroll', 'touchstart'];

        activityEvents.forEach((eventType) => {
            // Reset mocks for each event type test
            mockWarning.mockClear();
            vi.clearAllTimers();

            mount(createTestComponent());

            // Fast-forward to 1 hour 50 minutes (close to warning time but not yet)
            vi.advanceTimersByTime(110 * 60 * 1000);

            // Simulate activity to reset timer
            window.dispatchEvent(new Event(eventType));

            // Fast-forward 110 more minutes (should not warn yet because we reset)
            vi.advanceTimersByTime(110 * 60 * 1000);
            expect(mockWarning).not.toHaveBeenCalled();

            // Now fast-forward to warning time from the reset point (5 more minutes)
            vi.advanceTimersByTime(5 * 60 * 1000);

            // Now it should warn
            expect(mockWarning).toHaveBeenCalledWith(
                expect.stringContaining(
                    'Your session will expire in 5 minutes',
                ),
                10000,
            );
        });
    });

    it('should allow user activity to extend session indefinitely', () => {
        const mockPost = vi.fn();
        vi.mocked(router.post).mockImplementation(mockPost as any);

        mount(createTestComponent());

        // Simulate active user for 6 hours (3x session lifetime)
        // by triggering activity every 60 minutes
        for (let i = 0; i < 6; i++) {
            vi.advanceTimersByTime(60 * 60 * 1000); // 1 hour
            window.dispatchEvent(new Event('mousedown'));
            mockWarning.mockClear();
        }

        // Should never have warned or logged out
        expect(mockWarning).not.toHaveBeenCalled();
        expect(mockPost).not.toHaveBeenCalled();
    });

    it('should redirect to homepage after logout completes', () => {
        const mockPost = vi.fn((url, data, options) => {
            // Immediately call onFinish callback
            if (options?.onFinish) {
                options.onFinish();
            }
        });

        vi.mocked(router.post).mockImplementation(mockPost as any);

        mount(createTestComponent());

        // Fast-forward to logout time (2 hours + 1.5s logout delay)
        vi.advanceTimersByTime(120 * 60 * 1000 + 1500);

        // Should have redirected to homepage
        expect(window.location.href).toBe('/');
    });

    it('should not show multiple warnings', () => {
        mount(createTestComponent());

        // Fast-forward to warning time
        vi.advanceTimersByTime(115 * 60 * 1000);

        expect(mockWarning).toHaveBeenCalledTimes(1);

        // Fast-forward another minute - warning should not be shown again
        vi.advanceTimersByTime(60 * 1000);

        expect(mockWarning).toHaveBeenCalledTimes(1);
    });

    it('should periodically check timeout status', () => {
        mount(createTestComponent());

        // Fast-forward to just before warning time
        vi.advanceTimersByTime(114 * 60 * 1000 + 30 * 1000); // 1:54:30

        // Should not have warned yet
        expect(mockWarning).not.toHaveBeenCalled();

        // Advance to cross into warning window (1:55:00)
        vi.advanceTimersByTime(30 * 1000);

        // The periodic check runs every 60 seconds, so we need to wait for next check
        // Advance to next minute boundary
        vi.advanceTimersByTime(60 * 1000);

        // Now warning should be shown
        expect(mockWarning).toHaveBeenCalled();
    });

    it('should handle edge case of warning shown exactly at 115 minutes', () => {
        mount(createTestComponent());

        // Fast-forward to exactly 115 minutes
        vi.advanceTimersByTime(115 * 60 * 1000);

        // Warning should be shown
        expect(mockWarning).toHaveBeenCalledWith(
            expect.stringContaining('Your session will expire in 5 minutes'),
            10000,
        );
    });

    it('should cleanup timers and event listeners on component unmount', () => {
        const wrapper = mount(createTestComponent());

        // Fast-forward to just before warning time
        vi.advanceTimersByTime(114 * 60 * 1000);

        // Unmount the component (triggers cleanup)
        wrapper.unmount();

        // Fast-forward past the warning time
        vi.advanceTimersByTime(2 * 60 * 1000);

        // No warning should be shown because cleanup cleared the timers
        expect(mockWarning).not.toHaveBeenCalled();
    });

    it('should cleanup event listeners on component unmount', () => {
        const removeEventListenerSpy = vi.spyOn(window, 'removeEventListener');

        mount(createTestComponent()).unmount();

        // Should have removed all 4 event listeners (mousedown, keydown, scroll, touchstart)
        expect(removeEventListenerSpy).toHaveBeenCalledWith(
            'mousedown',
            expect.any(Function),
        );
        expect(removeEventListenerSpy).toHaveBeenCalledWith(
            'keydown',
            expect.any(Function),
        );
        expect(removeEventListenerSpy).toHaveBeenCalledWith(
            'scroll',
            expect.any(Function),
        );
        expect(removeEventListenerSpy).toHaveBeenCalledWith(
            'touchstart',
            expect.any(Function),
        );

        removeEventListenerSpy.mockRestore();
    });

    it('should clear timeout intervals on unmount', () => {
        const clearIntervalSpy = vi.spyOn(global, 'clearInterval');

        mount(createTestComponent()).unmount();

        // Should have cleared the interval
        expect(clearIntervalSpy).toHaveBeenCalled();

        clearIntervalSpy.mockRestore();
    });
});
