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

interface FunnelStage {
    stage: number;
    stageName: string;
    starts: number;
    conversions: number;
    conversionRate: number;
}

interface StateDistribution {
    stage: number;
    stageName: string;
    count: number;
}

const loading = ref(false);
const stages = ref<FunnelStage[]>([]);
const stateDistribution = ref<StateDistribution[]>([]);
const stats = ref({
    date: '',
    totalEntered: 0,
    totalConverted: 0,
    overallConversionRate: 0,
});

const loadData = async () => {
    loading.value = true;
    try {
        // Fetch from API
        const response = await fetch(
            `/api/admin/domain-analytics/funnels?date=${props.dateRange}`,
            {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            },
        );
        if (!response.ok) {
            throw new Error(
                `Failed to load funnel analytics: HTTP ${response.status}`,
            );
        }
        const data = await response.json();

        stages.value = data.stages || [];
        stateDistribution.value = data.stateDistribution || [];
        stats.value = data.stats || {
            date: '',
            totalEntered: 0,
            totalConverted: 0,
            overallConversionRate: 0,
        };

        emit('dataLoaded');
    } catch (error) {
        console.error('Failed to load funnel analytics:', error);
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
                :label="t('admin.domain_analytics.total_entered')"
                :value="stats.totalEntered"
                :loading="loading"
            />
            <StatCard
                :label="t('admin.domain_analytics.total_converted')"
                :value="stats.totalConverted"
                :loading="loading"
            />
            <StatCard
                :label="t('admin.domain_analytics.conversion_rate')"
                :value="`${stats.overallConversionRate.toFixed(1)}%`"
                :loading="loading"
            />
            <StatCard
                :label="t('admin.domain_analytics.date')"
                :value="stats.date"
                :loading="loading"
            />
        </div>

        <!-- Funnel Stages -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table class="w-full">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-sm font-semibold text-gray-900"
                        >
                            {{ t('admin.domain_analytics.stage') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-sm font-semibold text-gray-900"
                        >
                            {{ t('admin.domain_analytics.starts') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-sm font-semibold text-gray-900"
                        >
                            {{ t('admin.domain_analytics.conversions') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-sm font-semibold text-gray-900"
                        >
                            {{ t('admin.domain_analytics.conversion_rate') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr
                        v-for="stage in stages"
                        :key="stage.stage"
                        class="hover:bg-gray-50"
                    >
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            {{ stage.stageName }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-600">
                            {{ stage.starts }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-600">
                            {{ stage.conversions }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <span
                                :class="[
                                    'inline-flex rounded px-2 py-1 text-xs font-semibold',
                                    stage.conversionRate >= 75
                                        ? 'bg-green-100 text-green-800'
                                        : stage.conversionRate >= 50
                                          ? 'bg-yellow-100 text-yellow-800'
                                          : 'bg-red-100 text-red-800',
                                ]"
                            >
                                {{ stage.conversionRate.toFixed(1) }}%
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- State Distribution -->
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">
                {{ t('admin.domain_analytics.current_state') }}
            </h3>
            <div class="space-y-3">
                <div
                    v-for="state in stateDistribution"
                    :key="state.stage"
                    class="flex items-center justify-between border-b border-gray-100 pb-3 last:border-0"
                >
                    <span class="text-sm font-medium text-gray-900">{{
                        state.stageName
                    }}</span>
                    <span class="text-sm font-semibold text-gray-600">{{
                        state.count
                    }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
