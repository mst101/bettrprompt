<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { AlertCircle, CheckCircle } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

defineOptions({
    layout: AdminLayout,
});

interface AlertHistory {
    id: number;
    ruleName: string;
    errorCode: string;
    errorMessage: string;
    triggeredCount: number;
    lastTriggeredAt: string;
    acknowledgedAt: string | null;
    acknowledgedByName: string | null;
}

const loading = ref(false);
const alerts = ref<AlertHistory[]>([]);

const loadAlerts = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/admin/alerts', {
            headers: {
                Accept: 'application/json',
            },
        });

        if (response.ok) {
            const data = await response.json();
            alerts.value = data.alerts || [];
        }
    } catch (error) {
        console.error('Failed to load alerts:', error);
    } finally {
        loading.value = false;
    }
};

const acknowledgeAlert = async (alertId: number) => {
    try {
        const response = await fetch(
            `/api/admin/alerts/${alertId}/acknowledge`,
            {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
            },
        );

        if (response.ok) {
            await loadAlerts();
        }
    } catch (error) {
        console.error('Failed to acknowledge alert:', error);
    }
};

const formatTime = (timestamp: string): string => {
    return new Date(timestamp).toLocaleString();
};

onMounted(() => {
    loadAlerts();
});
</script>

<template>
    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Workflow Alerts</h1>
            <p class="mt-1 text-gray-600">
                View and manage workflow failure alerts
            </p>
        </div>

        <div v-if="loading" class="text-center text-gray-600">
            Loading alerts...
        </div>

        <div
            v-else-if="alerts.length === 0"
            class="rounded-lg border border-gray-200 bg-white p-8 text-center"
        >
            <CheckCircle class="mx-auto h-12 w-12 text-green-600" />
            <h3 class="mt-2 text-lg font-medium text-gray-900">No Alerts</h3>
            <p class="mt-1 text-gray-600">All systems are operating normally</p>
        </div>

        <div v-else class="rounded-lg border border-gray-200 bg-white">
            <div class="overflow-hidden">
                <table class="w-full">
                    <thead class="border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-sm font-semibold text-gray-900"
                            >
                                Alert
                            </th>
                            <th
                                class="px-6 py-3 text-left text-sm font-semibold text-gray-900"
                            >
                                Error Code
                            </th>
                            <th
                                class="px-6 py-3 text-center text-sm font-semibold text-gray-900"
                            >
                                Count
                            </th>
                            <th
                                class="px-6 py-3 text-left text-sm font-semibold text-gray-900"
                            >
                                Last Triggered
                            </th>
                            <th
                                class="px-6 py-3 text-left text-sm font-semibold text-gray-900"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-center text-sm font-semibold text-gray-900"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr
                            v-for="alert in alerts"
                            :key="alert.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-6 py-4 text-sm">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ alert.ruleName }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ alert.errorMessage }}
                                    </p>
                                </div>
                            </td>
                            <td
                                class="px-6 py-4 font-mono text-sm text-gray-600"
                            >
                                {{ alert.errorCode }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800"
                                >
                                    {{ alert.triggeredCount }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ formatTime(alert.lastTriggeredAt) }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div
                                    v-if="alert.acknowledgedAt"
                                    class="flex items-center gap-2"
                                >
                                    <CheckCircle
                                        class="h-4 w-4 text-green-600"
                                    />
                                    <span class="text-green-600"
                                        >Acknowledged</span
                                    >
                                    <span
                                        v-if="alert.acknowledgedByName"
                                        class="text-gray-600"
                                    >
                                        by {{ alert.acknowledgedByName }}
                                    </span>
                                </div>
                                <div v-else class="flex items-center gap-2">
                                    <AlertCircle class="h-4 w-4 text-red-600" />
                                    <span class="text-red-600">Pending</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button
                                    v-if="!alert.acknowledgedAt"
                                    class="inline-flex items-center rounded-md bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700 hover:bg-blue-100"
                                    @click="acknowledgeAlert(alert.id)"
                                >
                                    Acknowledge
                                </button>
                                <span v-else class="text-gray-500">
                                    Acknowledged
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
