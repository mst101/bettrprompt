<script setup lang="ts">
import { useAnalyticsDataFetch } from '@/Composables/data/useAnalyticsDataFetch';
import type { ErrorAnalyticsResource, WorkflowStageResource } from '@/Types';
import { AlertCircle, CheckCircle } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    dateFrom: string;
    dateTo: string;
}>();

const emit = defineEmits<{
    dataLoaded: [];
}>();

const { t } = useI18n();

const workflowStages = ref<WorkflowStageResource[]>([]);
const topErrors = ref<ErrorAnalyticsResource[]>([]);
const totalCost = ref(0);
const totalInputTokens = ref(0);
const totalOutputTokens = ref(0);

const { fetchData } = useAnalyticsDataFetch(
    '/api/admin/domain-analytics/workflows',
);

const costPerExecution = computed(() => {
    const totalExecutions = workflowStages.value.reduce(
        (sum, stage) => sum + stage.totalExecutions,
        0,
    );
    return totalExecutions > 0 ? totalCost.value / totalExecutions : 0;
});

const loadAnalytics = async () => {
    try {
        const data = await fetchData(props.dateFrom, props.dateTo);
        workflowStages.value = data.stages || [];
        topErrors.value = data.topErrors || [];
        totalCost.value = data.totalCost || 0;
        totalInputTokens.value = data.totalInputTokens || 0;
        totalOutputTokens.value = data.totalOutputTokens || 0;
        emit('dataLoaded');
    } catch {
        // Error already logged in composable
    }
};

onMounted(() => {
    loadAnalytics();
});

watch(
    () => [props.dateFrom, props.dateTo],
    () => {
        loadAnalytics();
    },
);
</script>

<template>
    <div class="space-y-6">
        <!-- Workflow Stages -->
        <div class="grid gap-6 md:grid-cols-3">
            <div
                v-for="stage in workflowStages"
                :key="stage.stage"
                class="rounded-lg border border-gray-200 bg-white p-6"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">
                            {{
                                t(
                                    `admin.domain_analytics.workflow_stage_${stage.stage}`,
                                )
                            }}
                        </p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ stage.successRate.toFixed(1) }}%
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ stage.totalExecutions }}
                            {{ t('admin.domain_analytics.executions') }}
                        </p>
                    </div>
                    <div
                        :class="[
                            'inline-flex h-12 w-12 items-center justify-center rounded-full',
                            stage.successRate >= 95
                                ? 'bg-green-100 text-green-600'
                                : stage.successRate >= 80
                                  ? 'bg-yellow-100 text-yellow-600'
                                  : 'bg-red-100 text-red-600',
                        ]"
                    >
                        <CheckCircle
                            v-if="stage.successRate >= 95"
                            :size="24"
                        />
                        <AlertCircle v-else :size="24" />
                    </div>
                </div>
                <div class="mt-4 space-y-2 text-xs text-gray-600">
                    <div class="flex justify-between">
                        <span
                            >{{ t('admin.domain_analytics.successful') }}:</span
                        >
                        <span class="font-medium">{{ stage.successful }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>{{ t('admin.domain_analytics.failed') }}:</span>
                        <span class="font-medium text-red-600">{{
                            stage.failed
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span
                            >{{
                                t('admin.domain_analytics.avg_duration')
                            }}:</span
                        >
                        <span class="font-medium"
                            >{{
                                stage.avgDurationMs?.toFixed(0) ?? 'N/A'
                            }}ms</span
                        >
                    </div>
                    <div class="flex justify-between">
                        <span>{{ t('admin.domain_analytics.avg_cost') }}:</span>
                        <span class="font-medium"
                            >${{ stage.avgCostUsd?.toFixed(4) ?? '0' }}</span
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- Cost Analysis -->
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">
                {{ t('admin.domain_analytics.cost_analysis') }}
            </h3>
            <div class="grid gap-6 md:grid-cols-3">
                <div>
                    <p class="text-sm text-gray-600">
                        {{ t('admin.domain_analytics.total_cost') }}
                    </p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">
                        ${{ totalCost.toFixed(2) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">
                        {{ t('admin.domain_analytics.total_tokens') }}
                    </p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">
                        {{
                            (
                                totalInputTokens + totalOutputTokens
                            ).toLocaleString()
                        }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">
                        {{ t('admin.domain_analytics.cost_per_execution') }}
                    </p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">
                        ${{ costPerExecution.toFixed(4) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Top Errors -->
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">
                {{ t('admin.domain_analytics.top_errors') }}
            </h3>
            <div v-if="topErrors.length > 0" class="space-y-3">
                <div
                    v-for="error in topErrors"
                    :key="error.errorCode"
                    class="flex items-center justify-between border-b border-gray-100 pb-3 last:border-0"
                >
                    <div>
                        <p class="text-sm font-medium text-gray-900">
                            {{ error.errorCode }}
                        </p>
                        <p class="text-xs text-gray-500">{{ error.message }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900">
                            {{ error.count }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ error.percentage.toFixed(1) }}%
                        </p>
                    </div>
                </div>
            </div>
            <div v-else class="text-center text-sm text-gray-500">
                {{ t('admin.domain_analytics.no_errors') }}
            </div>
        </div>
    </div>
</template>
