<script setup lang="ts">
import { Bell, X } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

interface Notification {
    id: number;
    alertId: number;
    ruleSlug: string;
    ruleName: string;
    errorCode: string;
    errorMessage: string;
    triggeredCount: number;
    createdAt: string;
}

const notifications = ref<Notification[]>([]);
const showPopover = ref(false);

const fetchNotifications = async () => {
    try {
        const response = await fetch('/api/admin/alert-notifications/pending', {
            headers: {
                Accept: 'application/json',
            },
        });

        if (response.ok) {
            const data = await response.json();
            notifications.value = data.notifications || [];
        }
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    }
};

const acknowledgeNotification = async (notificationId: number) => {
    try {
        const response = await fetch(
            `/api/admin/alert-notifications/${notificationId}/acknowledge`,
            {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
            },
        );

        if (response.ok) {
            // Remove notification from the list
            notifications.value = notifications.value.filter(
                (n) => n.id !== notificationId,
            );
        }
    } catch (error) {
        console.error('Failed to acknowledge notification:', error);
    }
};

onMounted(() => {
    fetchNotifications();

    // Refresh notifications every 30 seconds
    const interval = setInterval(fetchNotifications, 30000);

    return () => clearInterval(interval);
});
</script>

<template>
    <div class="relative">
        <button
            class="relative rounded-lg p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900"
            @click="showPopover = !showPopover"
        >
            <Bell :size="24" />
            <span
                v-if="notifications.length > 0"
                class="absolute -top-1 -right-1 inline-flex items-center justify-center rounded-full bg-red-500 px-2 py-0.5 text-xs font-bold text-white"
            >
                {{ notifications.length }}
            </span>
        </button>

        <!-- Notification Popover -->
        <div
            v-if="showPopover"
            class="absolute right-0 z-50 mt-2 w-80 rounded-lg border border-gray-200 bg-white shadow-lg"
        >
            <div class="border-b border-gray-200 px-4 py-3">
                <h3 class="text-sm font-semibold text-gray-900">Alerts</h3>
            </div>

            <div
                v-if="notifications.length === 0"
                class="px-4 py-6 text-center"
            >
                <p class="text-sm text-gray-500">No alerts</p>
            </div>

            <div
                v-else
                class="max-h-96 divide-y divide-gray-100 overflow-y-auto"
            >
                <div
                    v-for="notification in notifications"
                    :key="notification.id"
                    class="flex items-start justify-between border-b border-gray-100 px-4 py-3 hover:bg-gray-50"
                >
                    <div class="flex-1 pr-2">
                        <p class="text-sm font-semibold text-gray-900">
                            {{ notification.ruleName }}
                        </p>
                        <p class="text-xs text-gray-600">
                            {{ notification.errorCode }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ notification.errorMessage }}
                        </p>
                        <p
                            v-if="notification.triggeredCount > 1"
                            class="mt-1 text-xs text-orange-600"
                        >
                            Triggered {{ notification.triggeredCount }} times
                            (within 15 minutes)
                        </p>
                    </div>
                    <button
                        class="mt-1 flex-shrink-0 text-gray-400 hover:text-gray-600"
                        @click="acknowledgeNotification(notification.id)"
                    >
                        <X :size="16" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Close popover when clicking outside -->
        <div
            v-if="showPopover"
            class="fixed inset-0 z-40"
            @click="showPopover = false"
        />
    </div>
</template>
