import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { useNotification } from './useNotification';

/**
 * Composable for managing session timeout and automatic logout
 *
 * Automatically logs out users after the configured session lifetime
 * to prevent 419 CSRF errors from expired sessions.
 *
 * Session lifetime: 120 minutes (2 hours) as configured in .env
 */
export function useSessionTimeout() {
    const { warning } = useNotification();

    // Session lifetime in milliseconds (120 minutes = 7200000ms)
    const SESSION_LIFETIME_MS = 120 * 60 * 1000;

    // Warning shown 5 minutes before expiry
    const WARNING_BEFORE_EXPIRY_MS = 5 * 60 * 1000;

    // Timestamps
    const sessionStartTime = ref<number>(Date.now());
    const warningShown = ref<boolean>(false);

    let warningTimer: ReturnType<typeof setTimeout> | null = null;
    let logoutTimer: ReturnType<typeof setTimeout> | null = null;
    let activityCheckInterval: ReturnType<typeof setInterval> | null = null;

    /**
     * Reset the session timeout
     * Called on user activity (mouse move, keyboard, clicks)
     */
    const resetTimeout = () => {
        sessionStartTime.value = Date.now();
        warningShown.value = false;

        // Clear existing timers
        if (warningTimer) clearTimeout(warningTimer);
        if (logoutTimer) clearTimeout(logoutTimer);

        // Set new timers
        warningTimer = setTimeout(() => {
            showWarning();
        }, SESSION_LIFETIME_MS - WARNING_BEFORE_EXPIRY_MS);

        logoutTimer = setTimeout(() => {
            performLogout();
        }, SESSION_LIFETIME_MS);
    };

    /**
     * Show warning notification before logout
     */
    const showWarning = () => {
        if (warningShown.value) return;

        warningShown.value = true;
        warning(
            'Your session will expire in 5 minutes due to inactivity. Move your mouse or click anywhere to stay logged in.',
            10000, // Show for 10 seconds
        );
    };

    /**
     * Perform automatic logout
     */
    const performLogout = () => {
        warning(
            'Your session has expired due to inactivity. Logging out...',
            false, // Don't auto-dismiss
        );

        // Logout after a brief delay to show the message
        setTimeout(() => {
            router.post(
                '/logout',
                {},
                {
                    onFinish: () => {
                        // Force reload to clear any stale state
                        window.location.href = '/';
                    },
                },
            );
        }, 1500);
    };

    /**
     * Check if we're approaching timeout and show warning if needed
     */
    const checkTimeout = () => {
        const elapsed = Date.now() - sessionStartTime.value;
        const remaining = SESSION_LIFETIME_MS - elapsed;

        // Show warning if we're within the warning window and haven't shown it yet
        if (remaining <= WARNING_BEFORE_EXPIRY_MS && !warningShown.value) {
            showWarning();
        }
    };

    /**
     * Initialize session timeout tracking
     */
    const init = () => {
        // Set initial timers
        resetTimeout();

        // Track user activity to reset timeout
        const activityEvents = ['mousedown', 'keydown', 'scroll', 'touchstart'];

        activityEvents.forEach((event) => {
            window.addEventListener(event, resetTimeout, { passive: true });
        });

        // Periodically check timeout status
        activityCheckInterval = setInterval(checkTimeout, 60000); // Check every minute

        // Cleanup function
        return () => {
            if (warningTimer) clearTimeout(warningTimer);
            if (logoutTimer) clearTimeout(logoutTimer);
            if (activityCheckInterval) clearInterval(activityCheckInterval);

            activityEvents.forEach((event) => {
                window.removeEventListener(event, resetTimeout);
            });
        };
    };

    /**
     * Cleanup timers on component unmount
     */
    onMounted(() => {
        const cleanup = init();
        onUnmounted(cleanup);
    });

    return {
        resetTimeout,
        sessionStartTime,
    };
}
