<script setup lang="ts">
import TableHeaderSortable from '@/Components/Base/TableHeaderSortable.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import StatCard from './StatCard.vue';

interface PromptRunData {
    id: string;
    personalityType: string;
    framework: string;
    createdBy: string;
    taskDescription: string;
    status: string;
    createdAt: string;
    completedAt: string | null;
    durationMs: number | null;
}

interface AnalyticsData {
    stats: {
        totalRuns: number;
        completedRuns: number;
        failedRuns: number;
        processingRuns: number;
        successRate: number;
        avgDurationMs: number;
    };
    stageBreakdown: {
        processing: number;
        completed: number;
        failed: number;
    };
    runs: PromptRunData[];
}

const props = defineProps<{
    dateFrom: string;
    dateTo: string;
}>();

const emit = defineEmits<{
    dataLoaded: [];
}>();

const { t } = useI18n();
const { countryRoute } = useCountryRoute();

const loading = ref(false);
const data = ref<AnalyticsData | null>(null);
const sortBy = ref('createdAt');
const sortDirection = ref<'asc' | 'desc'>('desc');

const computedSortDirection = computed(() => {
    return sortDirection.value;
});

const loadData = async () => {
    loading.value = true;
    try {
        const response = await fetch(
            `/api/admin/domain-analytics/prompt-runs?start_date=${props.dateFrom}&end_date=${props.dateTo}&sort_by=${sortBy.value}&sort_direction=${sortDirection.value}`,
            {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            },
        );
        if (!response.ok) {
            throw new Error(
                `Failed to load prompt runs analytics: HTTP ${response.status}`,
            );
        }
        data.value = await response.json();
        emit('dataLoaded');
    } catch (error) {
        console.error('Failed to load prompt runs analytics:', error);
    } finally {
        loading.value = false;
    }
};

const handleSort = (column: string) => {
    const currentSortBy = sortBy.value;
    const currentDirection = sortDirection.value;

    if (currentSortBy === column && currentDirection === 'asc') {
        sortDirection.value = 'desc';
    } else if (currentSortBy === column && currentDirection === 'desc') {
        sortBy.value = 'createdAt';
        sortDirection.value = 'desc';
    } else {
        sortBy.value = column;
        sortDirection.value = 'asc';
    }

    loadData();
};

onMounted(() => {
    loadData();
});

const formatDate = (dateStr: string): string => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDuration = (ms: number | null): string => {
    if (ms === null) return 'N/A';
    const seconds = Math.floor(ms / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);

    if (hours > 0) {
        return `${hours}h ${minutes % 60}m`;
    }
    if (minutes > 0) {
        return `${minutes}m ${seconds % 60}s`;
    }
    return `${seconds}s`;
};

const getStatusColor = (status: string): string => {
    if (status === '2_completed') return 'bg-green-100 text-green-800';
    if (status.includes('failed')) return 'bg-red-100 text-red-800';
    if (status.includes('processing')) return 'bg-yellow-100 text-yellow-800';
    return 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status: string): string => {
    const statusMap: Record<string, string> = {
        '0_processing': 'Pre-Analysis',
        '0_completed': 'Pre-Analysis Done',
        '0_failed': 'Pre-Analysis Failed',
        '1_processing': 'Analyzing',
        '1_completed': 'Analysis Done',
        '1_failed': 'Analysis Failed',
        '2_processing': 'Generating',
        '2_completed': 'Complete',
        '2_failed': 'Generation Failed',
    };
    return statusMap[status] || status;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Stats Grid -->
        <div class="grid gap-6 md:grid-cols-3">
            <StatCard
                :label="t('admin.domain_analytics.total_runs')"
                :value="data?.stats.totalRuns || 0"
                :loading="loading"
            />
            <StatCard
                :label="t('admin.domain_analytics.success_rate')"
                :value="`${(data?.stats.successRate || 0).toFixed(1)}%`"
                :loading="loading"
            />
            <StatCard
                :label="t('admin.domain_analytics.avg_duration')"
                :value="formatDuration(data?.stats.avgDurationMs || 0)"
                :loading="loading"
            />
        </div>

        <!-- Stage Breakdown -->
        <div class="grid gap-6 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <p class="text-sm font-medium text-gray-600">
                    {{ t('admin.domain_analytics.completed') }}
                </p>
                <p class="mt-2 text-3xl font-bold text-green-600">
                    {{ data?.stageBreakdown.completed || 0 }}
                </p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <p class="text-sm font-medium text-gray-600">
                    {{ t('admin.domain_analytics.processing') }}
                </p>
                <p class="mt-2 text-3xl font-bold text-yellow-600">
                    {{ data?.stageBreakdown.processing || 0 }}
                </p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <p class="text-sm font-medium text-gray-600">
                    {{ t('admin.domain_analytics.failed') }}
                </p>
                <p class="mt-2 text-3xl font-bold text-red-600">
                    {{ data?.stageBreakdown.failed || 0 }}
                </p>
            </div>
        </div>

        <!-- Runs Table -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table class="w-full">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-sm font-semibold text-gray-900"
                        >
                            <TableHeaderSortable
                                column="taskDescription"
                                :current-sort="sortBy"
                                :sort-direction="computedSortDirection"
                                @sort="handleSort"
                            >
                                {{
                                    t('admin.domain_analytics.task_description')
                                }}
                            </TableHeaderSortable>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-sm font-semibold text-gray-900"
                        >
                            <TableHeaderSortable
                                column="personalityType"
                                :current-sort="sortBy"
                                :sort-direction="computedSortDirection"
                                @sort="handleSort"
                            >
                                {{
                                    t('admin.domain_analytics.personality_type')
                                }}
                            </TableHeaderSortable>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-sm font-semibold text-gray-900"
                        >
                            <TableHeaderSortable
                                column="framework"
                                :current-sort="sortBy"
                                :sort-direction="computedSortDirection"
                                @sort="handleSort"
                            >
                                {{ t('admin.domain_analytics.framework') }}
                            </TableHeaderSortable>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-sm font-semibold text-gray-900"
                        >
                            <TableHeaderSortable
                                column="createdBy"
                                :current-sort="sortBy"
                                :sort-direction="computedSortDirection"
                                @sort="handleSort"
                            >
                                {{ t('admin.domain_analytics.created_by') }}
                            </TableHeaderSortable>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-sm font-semibold text-gray-900"
                        >
                            <TableHeaderSortable
                                column="status"
                                :current-sort="sortBy"
                                :sort-direction="computedSortDirection"
                                @sort="handleSort"
                            >
                                {{ t('admin.domain_analytics.status') }}
                            </TableHeaderSortable>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-sm font-semibold text-gray-900"
                        >
                            <TableHeaderSortable
                                column="durationMs"
                                :current-sort="sortBy"
                                :sort-direction="computedSortDirection"
                                @sort="handleSort"
                            >
                                {{ t('admin.domain_analytics.duration') }}
                            </TableHeaderSortable>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-sm font-semibold text-gray-900"
                        >
                            <TableHeaderSortable
                                column="createdAt"
                                :current-sort="sortBy"
                                :sort-direction="computedSortDirection"
                                @sort="handleSort"
                            >
                                {{ t('admin.domain_analytics.created_at') }}
                            </TableHeaderSortable>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <Link
                        v-for="run in data?.runs"
                        :key="run.id"
                        :href="
                            countryRoute('admin.prompt-runs.show', {
                                promptRun: run.id,
                            })
                        "
                        as="tr"
                        class="cursor-pointer transition hover:bg-gray-50"
                    >
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            {{ run.taskDescription }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ run.personalityType }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ run.framework }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ run.createdBy }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span
                                :class="[
                                    'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                                    getStatusColor(run.status),
                                ]"
                            >
                                {{ getStatusLabel(run.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ formatDuration(run.durationMs) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ formatDate(run.createdAt) }}
                        </td>
                    </Link>
                </tbody>
            </table>
            <div
                v-if="!data?.runs || data.runs.length === 0"
                class="px-6 py-4 text-center text-sm text-gray-500"
            >
                {{ t('admin.domain_analytics.no_runs') }}
            </div>
        </div>
    </div>
</template>
