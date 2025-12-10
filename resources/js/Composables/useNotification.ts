import { computed, ref, type Ref } from 'vue';

export type NotificationType = 'success' | 'error' | 'warning' | 'info';

export interface Notification {
    id: string;
    message: string;
    type: NotificationType;
    autoDismiss: boolean;
    dismissDelay: number;
}

// Global notifications store (reactive ref)
const notifications: Ref<Notification[]> = ref([]);

/**
 * Composable for displaying notifications/toasts throughout the app
 *
 * @example
 * const { add, remove, notifications } = useNotification();
 *
 * // Add a notification
 * add({
 *     message: 'Operation successful!',
 *     type: 'success',
 * });
 *
 * // Add a notification that doesn't auto-dismiss
 * add({
 *     message: 'Session expired. Reloading...',
 *     type: 'error',
 *     autoDismiss: false,
 * });
 */
export function useNotification() {
    /**
     * Add a new notification
     */
    const add = (
        notification: Omit<Notification, 'id'>,
        options?: { autoDismissDelay?: number },
    ): string => {
        const id = `notification-${Date.now()}-${Math.random()}`;
        const dismissDelay =
            options?.autoDismissDelay ?? notification.dismissDelay;

        const newNotification: Notification = {
            ...notification,
            id,
            dismissDelay,
        };

        notifications.value.push(newNotification);

        // Auto-dismiss if enabled
        if (newNotification.autoDismiss) {
            setTimeout(() => {
                remove(id);
            }, dismissDelay);
        }

        return id;
    };

    /**
     * Remove a notification by ID
     */
    const remove = (id: string) => {
        const index = notifications.value.findIndex((n) => n.id === id);
        if (index > -1) {
            notifications.value.splice(index, 1);
        }
    };

    /**
     * Remove all notifications
     */
    const clear = () => {
        notifications.value = [];
    };

    /**
     * Add a success notification
     */
    const success = (message: string, dismissDelay?: number) => {
        return add(
            {
                message,
                type: 'success',
                autoDismiss: true,
                dismissDelay: dismissDelay ?? 3000,
            },
            { autoDismissDelay: dismissDelay },
        );
    };

    /**
     * Add an error notification
     */
    const error = (
        message: string,
        autoDismiss: boolean = true,
        dismissDelay?: number,
    ) => {
        return add(
            {
                message,
                type: 'error',
                autoDismiss,
                dismissDelay: dismissDelay ?? 5000,
            },
            { autoDismissDelay: dismissDelay },
        );
    };

    /**
     * Add a warning notification
     */
    const warning = (message: string, dismissDelay?: number) => {
        return add(
            {
                message,
                type: 'warning',
                autoDismiss: true,
                dismissDelay: dismissDelay ?? 4000,
            },
            { autoDismissDelay: dismissDelay },
        );
    };

    /**
     * Add an info notification
     */
    const info = (message: string, dismissDelay?: number) => {
        return add(
            {
                message,
                type: 'info',
                autoDismiss: true,
                dismissDelay: dismissDelay ?? 3000,
            },
            { autoDismissDelay: dismissDelay },
        );
    };

    return {
        notifications: computed(() => notifications.value),
        add,
        remove,
        clear,
        success,
        error,
        warning,
        info,
    };
}
