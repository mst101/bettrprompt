<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import StatCard from './StatCard.vue';

const props = defineProps<{
    dateRange: string;
}>();

const emit = defineEmits<{
    dataLoaded: [];
}>();

const { t } = useI18n();

interface FrameworkData {
    framework: string;
    timesRecommended: number;
    timesChosen: number;
    acceptanceRate: number;
    avgRating: number | null;
    copyRate: number;
}

const loading = ref(false);
const frameworks = ref<FrameworkData[]>([]);
const topFrameworks = ref<FrameworkData[]>([]);
const stats = ref({
    totalRecommendations: 0,
    acceptanceRate: 0,
    avgRating: 0,
    copyRate: 0,
});

const loadData = async () => {
    loading.value = true;
    try {
        // Fetch from API
        const response = await fetch(
            `/api/admin/domain-analytics/frameworks?date=${props.dateRange}`,
            {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            },
        );
        if (!response.ok) {
            throw new Error(
                `Failed to load framework analytics: HTTP ${response.status}`,
            );
        }
        const data = await response.json();

        frameworks.value = data.frameworks || [];
        stats.value = data.stats || {};
        topFrameworks.value = frameworks.value.slice(0, 5);

        emit('dataLoaded');
    } catch (error) {
        console.error('Failed to load framework analytics:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadData();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Stats Grid -->
        <div class="grid gap-6 md:grid-cols-4">
            <StatCard
                :label="t('admin.domain_analytics.total_recommendations')"
                :value="stats.totalRecommendations"
                :loading="loading"
            />
            <StatCard
                :label="t('admin.domain_analytics.acceptance_rate')"
                :value="`${stats.acceptanceRate.toFixed(1)}%`"
                :loading="loading"
            />
            <StatCard
                :label="t('admin.domain_analytics.avg_rating')"
                :value="stats.avgRating.toFixed(2)"
                :loading="loading"
            />
            <StatCard
                :label="t('admin.domain_analytics.copy_rate')"
                :value="`${stats.copyRate.toFixed(1)}%`"
                :loading="loading"
            />
        </div>

        <!-- Framework Comparison Table -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table class="w-full">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-sm font-semibold text-gray-900"
                        >
                            {{ t('admin.domain_analytics.framework') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-sm font-semibold text-gray-900"
                        >
                            {{ t('admin.domain_analytics.recommended') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-sm font-semibold text-gray-900"
                        >
                            {{ t('admin.domain_analytics.chosen') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-sm font-semibold text-gray-900"
                        >
                            {{ t('admin.domain_analytics.acceptance_rate') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-sm font-semibold text-gray-900"
                        >
                            {{ t('admin.domain_analytics.avg_rating') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-sm font-semibold text-gray-900"
                        >
                            {{ t('admin.domain_analytics.copy_rate') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr
                        v-for="framework in frameworks"
                        :key="framework.framework"
                        class="hover:bg-gray-50"
                    >
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            {{ framework.framework }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-600">
                            {{ framework.timesRecommended }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-600">
                            {{ framework.timesChosen }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <span
                                :class="[
                                    'inline-flex rounded px-2 py-1 text-xs font-semibold',
                                    framework.acceptanceRate >= 75
                                        ? 'bg-green-100 text-green-800'
                                        : framework.acceptanceRate >= 50
                                          ? 'bg-yellow-100 text-yellow-800'
                                          : 'bg-red-100 text-red-800',
                                ]"
                            >
                                {{ framework.acceptanceRate.toFixed(1) }}%
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-600">
                            {{ framework.avgRating?.toFixed(2) ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-600">
                            {{ framework.copyRate.toFixed(1) }}%
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Top Frameworks by Acceptance -->
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">
                {{ t('admin.domain_analytics.top_frameworks') }}
            </h3>
            <div class="space-y-3">
                <div
                    v-for="(framework, index) in topFrameworks"
                    :key="framework.framework"
                    class="flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-600"
                        >
                            {{ index + 1 }}
                        </span>
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ framework.framework }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ framework.timesRecommended }}
                                {{
                                    t('admin.domain_analytics.recommendations')
                                }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-semibold text-gray-900">
                            {{ framework.acceptanceRate.toFixed(1) }}%
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ t('admin.domain_analytics.acceptance') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
