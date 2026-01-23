<script setup lang="ts">
import { useAnalyticsDataFetch } from '@/Composables/data/useAnalyticsDataFetch';
import { onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import StatCard from './StatCard.vue';

const props = defineProps<{
    dateFrom: string;
    dateTo: string;
}>();

const emit = defineEmits<{
    dataLoaded: [];
}>();

const { t } = useI18n();

interface QuestionData {
    questionId: string;
    timesShown: number;
    answerRate: number;
    skipRate: number;
    avgTimeMs: number | null;
    isEffective: boolean;
}

const questions = ref<QuestionData[]>([]);
const mostEffective = ref<QuestionData[]>([]);
const needsImprovement = ref<QuestionData[]>([]);
const stats = ref({
    totalShown: 0,
    answerRate: 0,
    skipRate: 0,
    avgTimeMs: 0,
});

const { loading, fetchData } = useAnalyticsDataFetch(
    '/api/admin/domain-analytics/questions',
);

const loadAnalytics = async () => {
    try {
        const data = await fetchData(props.dateFrom, props.dateTo);
        questions.value = data.questions || [];
        stats.value = data.stats || {};
        mostEffective.value = questions.value
            .filter((q) => q.isEffective)
            .sort((a, b) => b.answerRate - a.answerRate)
            .slice(0, 5);
        needsImprovement.value = questions.value
            .filter((q) => !q.isEffective)
            .sort((a, b) => b.skipRate - a.skipRate)
            .slice(0, 5);
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
        <!-- Stats Grid -->
        <div class="grid gap-6 md:grid-cols-4">
            <StatCard
                :label="t('admin.domain_analytics.total_shown')"
                :value="stats.totalShown"
                :loading="loading"
            />
            <StatCard
                :label="t('admin.domain_analytics.answer_rate')"
                :value="`${stats.answerRate.toFixed(1)}%`"
                :loading="loading"
            />
            <StatCard
                :label="t('admin.domain_analytics.skip_rate')"
                :value="`${stats.skipRate.toFixed(1)}%`"
                :loading="loading"
            />
            <StatCard
                :label="t('admin.domain_analytics.avg_time')"
                :value="`${stats.avgTimeMs.toFixed(0)}ms`"
                :loading="loading"
            />
        </div>

        <!-- Questions Table -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table class="w-full">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-sm font-semibold text-gray-900"
                        >
                            {{ t('admin.domain_analytics.question_id') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-sm font-semibold text-gray-900"
                        >
                            {{ t('admin.domain_analytics.times_shown') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-sm font-semibold text-gray-900"
                        >
                            {{ t('admin.domain_analytics.answer_rate') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-sm font-semibold text-gray-900"
                        >
                            {{ t('admin.domain_analytics.skip_rate') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-sm font-semibold text-gray-900"
                        >
                            {{ t('admin.domain_analytics.avg_time') }}
                        </th>
                        <th
                            class="px-6 py-3 text-center text-sm font-semibold text-gray-900"
                        >
                            {{ t('admin.domain_analytics.effectiveness') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr
                        v-for="question in questions"
                        :key="question.questionId"
                        class="hover:bg-gray-50"
                    >
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            {{ question.questionId }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-600">
                            {{ question.timesShown }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <span
                                :class="[
                                    'inline-flex rounded px-2 py-1 text-xs font-semibold',
                                    question.answerRate >= 75
                                        ? 'bg-green-100 text-green-800'
                                        : question.answerRate >= 50
                                          ? 'bg-yellow-100 text-yellow-800'
                                          : 'bg-red-100 text-red-800',
                                ]"
                            >
                                {{ question.answerRate.toFixed(1) }}%
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-600">
                            {{ question.skipRate.toFixed(1) }}%
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-600">
                            {{ question.avgTimeMs?.toFixed(0) ?? 'N/A' }}ms
                        </td>
                        <td class="px-6 py-4 text-center text-sm">
                            <span
                                :class="[
                                    'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                                    question.isEffective
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-gray-100 text-gray-800',
                                ]"
                            >
                                {{
                                    question.isEffective
                                        ? t('admin.domain_analytics.effective')
                                        : t(
                                              'admin.domain_analytics.needs_improvement',
                                          )
                                }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Most Effective Questions -->
        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">
                    {{ t('admin.domain_analytics.most_effective') }}
                </h3>
                <div class="space-y-3">
                    <div
                        v-for="question in mostEffective"
                        :key="question.questionId"
                        class="flex items-center justify-between border-b border-gray-100 pb-3 last:border-0"
                    >
                        <span class="text-sm font-medium text-gray-900">{{
                            question.questionId
                        }}</span>
                        <span
                            class="inline-flex items-center gap-1 text-sm text-green-600"
                        >
                            <span>✓</span>
                            {{ question.answerRate.toFixed(0) }}%
                        </span>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">
                    {{ t('admin.domain_analytics.needs_improvement') }}
                </h3>
                <div class="space-y-3">
                    <div
                        v-for="question in needsImprovement"
                        :key="question.questionId"
                        class="flex items-center justify-between border-b border-gray-100 pb-3 last:border-0"
                    >
                        <span class="text-sm font-medium text-gray-900">{{
                            question.questionId
                        }}</span>
                        <span
                            class="inline-flex items-center gap-1 text-sm text-red-600"
                        >
                            <span>✗</span>
                            {{ question.skipRate.toFixed(0) }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
