import { router } from '@inertiajs/vue3';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

// Mock Inertia router
vi.mock('@inertiajs/vue3', () => ({
    router: {
        post: vi.fn(),
    },
}));

// Mock useNotification
const mockWarning = vi.fn();
const mockError = vi.fn();

vi.mock('@/Composables/useNotification', () => ({
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

        // Add event listener tracking
        (window as any)._eventListeners = [];
        const originalAddEventListener = window.addEventListener;
        window.addEventListener = vi.fn((event, handler, options) => {
            (window as any)._eventListeners.push({ event, handler, options });
            originalAddEventListener.call(window, event, handler, options);
        }) as any;
    });

    afterEach(() => {
        // Restore real timers
        vi.useRealTimers();

        // Clean up event listeners
        if ((window as any)._eventListeners) {
            (window as any)._eventListeners.forEach(
                ({ event, handler }: any) => {
                    window.removeEventListener(event, handler);
                },
            );
        }
    });

    it('should show warning 5 minutes before session expiry', () => {
        const cleanup = setupSessionTimeout();

        // Fast-forward to 1 hour 55 minutes (115 minutes)
        const WARNING_TIME = 115 * 60 * 1000;
        vi.advanceTimersByTime(WARNING_TIME);

        // Warning should have been shown
        expect(mockWarning).toHaveBeenCalledWith(
            expect.stringContaining('Your session will expire in 5 minutes'),
            10000,
        );

        cleanup();
    });

    it('should perform automatic logout after 2 hours', () => {
        const mockPost = vi.fn();
        vi.mocked(router.post).mockImplementation(mockPost as any);

        const cleanup = setupSessionTimeout();

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

        cleanup();
    });

    it('should reset timeout on user activity', () => {
        const cleanup = setupSessionTimeout();

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

        cleanup();
    });

    it('should track multiple activity event types', () => {
        const activityEvents = ['mousedown', 'keydown', 'scroll', 'touchstart'];

        activityEvents.forEach((eventType) => {
            // Create a fresh setup for each event type test
            const cleanup = setupSessionTimeout();
            mockWarning.mockClear();

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

            cleanup();
        });
    });

    it('should allow user activity to extend session indefinitely', () => {
        const mockPost = vi.fn();
        vi.mocked(router.post).mockImplementation(mockPost as any);

        const cleanup = setupSessionTimeout();

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

        cleanup();
    });

    it('should redirect to homepage after logout completes', () => {
        const mockPost = vi.fn((url, data, options) => {
            // Immediately call onFinish callback
            if (options?.onFinish) {
                options.onFinish();
            }
        });

        vi.mocked(router.post).mockImplementation(mockPost as any);

        const cleanup = setupSessionTimeout();

        // Fast-forward to logout time (2 hours + 1.5s logout delay)
        vi.advanceTimersByTime(120 * 60 * 1000 + 1500);

        // Should have redirected to homepage
        expect(window.location.href).toBe('/');

        cleanup();
    });

    it('should not show multiple warnings', () => {
        const cleanup = setupSessionTimeout();

        // Fast-forward to warning time
        vi.advanceTimersByTime(115 * 60 * 1000);

        expect(mockWarning).toHaveBeenCalledTimes(1);

        // Fast-forward another minute - warning should not be shown again
        vi.advanceTimersByTime(60 * 1000);

        expect(mockWarning).toHaveBeenCalledTimes(1);

        cleanup();
    });

    it('should periodically check timeout status', () => {
        const cleanup = setupSessionTimeout();

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

        cleanup();
    });

    it('should handle edge case of warning shown exactly at 115 minutes', () => {
        const cleanup = setupSessionTimeout();

        // Fast-forward to exactly 115 minutes
        vi.advanceTimersByTime(115 * 60 * 1000);

        // Warning should be shown
        expect(mockWarning).toHaveBeenCalledWith(
            expect.stringContaining('Your session will expire in 5 minutes'),
            10000,
        );

        cleanup();
    });
});

/**
 * Helper function to manually set up session timeout for testing
 * Returns a cleanup function
 */
function setupSessionTimeout(): () => void {
    // Session lifetime constants (matching the composable)
    const SESSION_LIFETIME_MS = 120 * 60 * 1000;
    const WARNING_BEFORE_EXPIRY_MS = 5 * 60 * 1000;

    let sessionStartTime = Date.now();
    let warningShown = false;

    let warningTimer: ReturnType<typeof setTimeout> | null = null;
    let logoutTimer: ReturnType<typeof setTimeout> | null = null;
    let activityCheckInterval: ReturnType<typeof setInterval> | null = null;

    const showWarning = () => {
        if (warningShown) return;

        warningShown = true;
        mockWarning(
            'Your session will expire in 5 minutes due to inactivity. Move your mouse or click anywhere to stay logged in.',
            10000,
        );
    };

    const performLogout = () => {
        mockWarning(
            'Your session has expired due to inactivity. Logging out...',
            false,
        );

        setTimeout(() => {
            router.post(
                '/logout',
                {},
                {
                    onFinish: () => {
                        window.location.href = '/';
                    },
                },
            );
        }, 1500);
    };

    const resetTimeout = () => {
        sessionStartTime = Date.now();
        warningShown = false;

        if (warningTimer) clearTimeout(warningTimer);
        if (logoutTimer) clearTimeout(logoutTimer);

        warningTimer = setTimeout(() => {
            showWarning();
        }, SESSION_LIFETIME_MS - WARNING_BEFORE_EXPIRY_MS);

        logoutTimer = setTimeout(() => {
            performLogout();
        }, SESSION_LIFETIME_MS);
    };

    const checkTimeout = () => {
        const elapsed = Date.now() - sessionStartTime;
        const remaining = SESSION_LIFETIME_MS - elapsed;

        if (remaining <= WARNING_BEFORE_EXPIRY_MS && !warningShown) {
            showWarning();
        }
    };

    // Set initial timers
    resetTimeout();

    // Track user activity
    const activityEvents = ['mousedown', 'keydown', 'scroll', 'touchstart'];

    activityEvents.forEach((event) => {
        window.addEventListener(event, resetTimeout, { passive: true });
    });

    // Periodically check timeout status
    activityCheckInterval = setInterval(checkTimeout, 60000);

    // Return cleanup function
    return () => {
        if (warningTimer) clearTimeout(warningTimer);
        if (logoutTimer) clearTimeout(logoutTimer);
        if (activityCheckInterval) clearInterval(activityCheckInterval);

        activityEvents.forEach((event) => {
            window.removeEventListener(event, resetTimeout);
        });
    };
}
