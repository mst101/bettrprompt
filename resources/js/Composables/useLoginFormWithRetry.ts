import { useNotification } from '@/Composables/ui/useNotification';
import { getCsrfToken } from '@/Utils/cookies';
import { useForm } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

/**
 * Custom hook for login form submission with automatic 419 retry
 *
 * Handles expired CSRF tokens by refreshing them and retrying the login.
 * This prevents "Page Expired" errors when logging in after session timeout.
 *
 * Works by:
 * 1. Listening for 'csrf-token-expired' events from the global error handler
 * 2. Refreshing the CSRF token by fetching a public page
 * 3. Retrying the login form submission automatically
 */
export function useLoginFormWithRetry(onSuccess: () => void) {
    const { warning } = useNotification();
    const retryCount = ref(0);
    const maxRetries = 2;
    let csrfExpiredHandler: ((event: Event) => void) | null = null;

    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    /**
     * Refresh CSRF token by fetching a public page
     * This creates a new session and generates a fresh CSRF token
     */
    const refreshCsrfToken = async (): Promise<boolean> => {
        try {
            const response = await fetch('/', {
                method: 'GET',
                credentials: 'include',
            });

            if (!response.ok) {
                return false;
            }

            // Wait a moment for the meta tag to be updated
            await new Promise((resolve) => setTimeout(resolve, 200));

            const newToken = getCsrfToken();
            if (newToken) {
                return true;
            }

            return false;
        } catch {
            return false;
        }
    };

    /**
     * Handle CSRF token expiry by retrying with a fresh token
     */
    const handleCsrfTokenExpired = async () => {
        if (retryCount.value >= maxRetries) {
            // Let the global handler reload the page
            return;
        }

        retryCount.value++;
        warning(
            `Session expired. Refreshing and retrying... (Attempt ${retryCount.value}/${maxRetries})`,
        );

        // Mark that we're handling this event
        window.csrfTokenRefreshHandled = true;

        const tokenRefreshed = await refreshCsrfToken();

        if (tokenRefreshed) {
            // Retry the form submission
            submit();
        } else {
            // Let the global handler reload the page
            window.csrfTokenRefreshHandled = false;
        }
    };

    /**
     * Submit the login form
     */
    const submit = () => {
        form.post(route('login'), {
            onFinish: () => {
                form.reset('password');
            },
            onSuccess: () => {
                retryCount.value = 0;
                onSuccess();
            },
            onError: () => {
                // Form validation errors are handled by Inertia
            },
        });
    };

    /**
     * Set up listeners when component mounts
     */
    onMounted(() => {
        csrfExpiredHandler = handleCsrfTokenExpired as (event: Event) => void;
        window.addEventListener('csrf-token-expired', csrfExpiredHandler);
    });

    /**
     * Clean up listeners when component unmounts
     */
    onUnmounted(() => {
        if (csrfExpiredHandler) {
            window.removeEventListener(
                'csrf-token-expired',
                csrfExpiredHandler,
            );
        }
    });

    return {
        form,
        submit,
    };
}
