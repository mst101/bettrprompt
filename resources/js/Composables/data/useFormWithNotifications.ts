import { useNotification } from '@/Composables/ui/useNotification';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

export interface UseFormWithNotificationsOptions {
    successMessage?: string;
    errorMessage?: string;
}

/**
 * Composable that combines Inertia form handling with automatic notification displays
 *
 * Eliminates boilerplate by automatically showing:
 * - Success notification when form submission completes
 * - Error notification when validation errors occur
 *
 * @example
 * ```typescript
 * const form = useFormWithNotifications(
 *     { name: '', email: '' },
 *     { successMessage: t('profile.updated') }
 * );
 * ```
 */
export function useFormWithNotifications<T extends Record<string, unknown>>(
    initialData: T,
    options?: UseFormWithNotificationsOptions,
) {
    const { success, error } = useNotification();
    const form = useForm(initialData);

    // Show success notification when form submission succeeds
    watch(
        () => form.recentlySuccessful,
        (value) => {
            if (value && options?.successMessage) {
                success(options.successMessage);
            }
        },
    );

    // Show error notification when validation errors occur
    watch(
        () => Object.keys(form.errors).length > 0,
        (hasErrors) => {
            if (hasErrors) {
                const errorMessage = Object.values(form.errors)[0];
                if (typeof errorMessage === 'string') {
                    error(options?.errorMessage || errorMessage);
                }
            }
        },
    );

    return form;
}
