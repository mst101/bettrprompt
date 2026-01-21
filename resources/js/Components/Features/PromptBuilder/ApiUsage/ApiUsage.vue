<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import type { ApiUsageResource, ClaudeModel } from '@/Types';
import { computed } from 'vue';

interface Props {
    preAnalysisUsage: ApiUsageResource | ApiUsageResource[] | null;
    analysisUsage: ApiUsageResource | ApiUsageResource[] | null;
    generationUsage: ApiUsageResource | ApiUsageResource[] | null;
    claudeModels?: ClaudeModel[];
}

const props = withDefaults(defineProps<Props>(), {
    claudeModels: () => [],
});

// Normalise usage data to array format
const normaliseUsage = (
    usage: ApiUsageResource | ApiUsageResource[] | null,
): ApiUsageResource[] => {
    if (!usage) return [];
    if (Array.isArray(usage)) return usage;
    return [usage];
};

const totalTokens = (usage: ApiUsageResource | null) => {
    if (!usage) return 0;
    return usage.inputTokens + usage.outputTokens;
};

const totalTokensForArray = (usageArray: ApiUsageResource[]): number => {
    return usageArray.reduce((sum, usage) => sum + totalTokens(usage), 0);
};

const formatNumber = (num: number) => {
    return num.toLocaleString();
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 4,
    }).format(amount);
};

const getModelPricing = (modelId: string): ClaudeModel | undefined => {
    return props.claudeModels?.find((m) => m.id === modelId);
};

const calculateCost = (usage: ApiUsageResource | null) => {
    if (!usage) return 0;

    const model = getModelPricing(usage.model);
    if (!model) return 0;

    const inputCost = (usage.inputTokens / 1_000_000) * model.inputCostPerMtok;
    const outputCost =
        (usage.outputTokens / 1_000_000) * model.outputCostPerMtok;

    return inputCost + outputCost;
};

const calculateCostForArray = (usageArray: ApiUsageResource[]): number => {
    return usageArray.reduce((sum, usage) => sum + calculateCost(usage), 0);
};

const grandTotal = () => {
    const preAnalysisArray = normaliseUsage(props.preAnalysisUsage);
    const analysisArray = normaliseUsage(props.analysisUsage);
    const generationArray = normaliseUsage(props.generationUsage);

    return (
        totalTokensForArray(preAnalysisArray) +
        totalTokensForArray(analysisArray) +
        totalTokensForArray(generationArray)
    );
};

const grandTotalCost = computed(() => {
    const preAnalysisArray = normaliseUsage(props.preAnalysisUsage);
    const analysisArray = normaliseUsage(props.analysisUsage);
    const generationArray = normaliseUsage(props.generationUsage);

    return (
        calculateCostForArray(preAnalysisArray) +
        calculateCostForArray(analysisArray) +
        calculateCostForArray(generationArray)
    );
});

const hasCostData = computed(() => {
    return props.claudeModels && props.claudeModels.length > 0;
});
</script>

<template>
    <Card class="space-y-6">
        <h2 class="text-lg font-semibold text-indigo-900">
            {{ $t('promptBuilder.components.apiUsage.title') }}
        </h2>

        <!-- Pre-Analysis Workflow -->
        <div
            v-if="preAnalysisUsage"
            class="rounded-lg bg-indigo-50 p-4 dark:bg-indigo-100"
        >
            <h3 class="mb-3 text-sm font-medium text-indigo-900">
                {{ $t('promptBuilder.components.apiUsage.preAnalysis') }}
            </h3>
            <!-- Mobile: Stacked layout -->
            <div
                v-for="(usage, index) in normaliseUsage(preAnalysisUsage)"
                :key="`pre-mobile-${index}`"
                class="space-y-2 md:hidden"
            >
                <div
                    v-if="normaliseUsage(preAnalysisUsage).length > 1"
                    class="text-xs font-medium text-indigo-600"
                >
                    {{
                        $t('promptBuilder.components.apiUsage.pass', {
                            number: index + 1,
                        })
                    }}
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">
                        {{ $t('promptBuilder.components.apiUsage.modelLabel') }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{ usage.model }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">
                        {{
                            $t(
                                'promptBuilder.components.apiUsage.inputTokensLabel',
                            )
                        }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(usage.input_tokens) }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">
                        {{
                            $t(
                                'promptBuilder.components.apiUsage.outputTokensLabel',
                            )
                        }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(usage.output_tokens) }}
                    </span>
                </div>
                <div
                    class="flex justify-between border-t border-indigo-200 pt-2 text-sm font-medium"
                >
                    <span class="text-indigo-900">
                        {{ $t('promptBuilder.components.apiUsage.totalLabel') }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(totalTokens(usage)) }}
                    </span>
                </div>
                <div
                    v-if="hasCostData && calculateCost(usage) > 0"
                    class="flex justify-between text-sm"
                >
                    <span class="text-indigo-600">
                        {{ $t('promptBuilder.components.apiUsage.costLabel') }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{ formatCurrency(calculateCost(usage)) }}
                    </span>
                </div>
                <div
                    v-if="index < normaliseUsage(preAnalysisUsage).length - 1"
                    class="border-t border-indigo-200"
                />
            </div>

            <!-- Desktop: Table layout -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-indigo-200">
                            <th
                                class="px-2 py-2 text-left font-medium text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.model',
                                    )
                                }}
                            </th>
                            <th
                                class="w-28 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.inputTokens',
                                    )
                                }}
                            </th>
                            <th
                                class="w-28 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.outputTokens',
                                    )
                                }}
                            </th>
                            <th
                                class="w-24 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.total',
                                    )
                                }}
                            </th>
                            <th
                                v-if="hasCostData"
                                class="w-20 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.costHeader',
                                    )
                                }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(usage, index) in normaliseUsage(
                                preAnalysisUsage,
                            )"
                            :key="`pre-desktop-${index}`"
                        >
                            <td class="px-2 py-2 font-mono text-indigo-900">
                                {{ usage.model }}
                                <span
                                    v-if="
                                        normaliseUsage(preAnalysisUsage)
                                            .length > 1
                                    "
                                    class="ml-2 text-xs text-indigo-600"
                                >
                                    ({{
                                        $t(
                                            'promptBuilder.components.apiUsage.pass',
                                            {
                                                number: index + 1,
                                            },
                                        )
                                    }})
                                </span>
                            </td>
                            <td
                                class="w-28 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{ formatNumber(usage.input_tokens) }}
                            </td>
                            <td
                                class="w-28 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{ formatNumber(usage.output_tokens) }}
                            </td>
                            <td
                                class="w-24 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{ formatNumber(totalTokens(usage)) }}
                            </td>
                            <td
                                v-if="hasCostData"
                                class="w-20 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{
                                    calculateCost(usage) > 0
                                        ? formatCurrency(calculateCost(usage))
                                        : '—'
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Analysis Workflow -->
        <div
            v-if="analysisUsage"
            class="rounded-lg bg-indigo-50 p-4 dark:bg-indigo-100"
        >
            <h3 class="mb-3 text-sm font-medium text-indigo-900">
                {{ $t('promptBuilder.components.apiUsage.analysis') }}
            </h3>
            <!-- Mobile: Stacked layout -->
            <div
                v-for="(usage, index) in normaliseUsage(analysisUsage)"
                :key="`analysis-mobile-${index}`"
                class="space-y-2 md:hidden"
            >
                <div
                    v-if="normaliseUsage(analysisUsage).length > 1"
                    class="text-xs font-medium text-indigo-600"
                >
                    {{
                        $t('promptBuilder.components.apiUsage.pass', {
                            number: index + 1,
                        })
                    }}
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">
                        {{ $t('promptBuilder.components.apiUsage.modelLabel') }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{ usage.model }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">
                        {{
                            $t(
                                'promptBuilder.components.apiUsage.inputTokensLabel',
                            )
                        }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(usage.input_tokens) }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">
                        {{
                            $t(
                                'promptBuilder.components.apiUsage.outputTokensLabel',
                            )
                        }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(usage.output_tokens) }}
                    </span>
                </div>
                <div
                    class="flex justify-between border-t border-indigo-200 pt-2 text-sm font-medium"
                >
                    <span class="text-indigo-900">
                        {{ $t('promptBuilder.components.apiUsage.totalLabel') }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(totalTokens(usage)) }}
                    </span>
                </div>
                <div
                    v-if="hasCostData && calculateCost(usage) > 0"
                    class="flex justify-between text-sm"
                >
                    <span class="text-indigo-600">
                        {{ $t('promptBuilder.components.apiUsage.costLabel') }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{ formatCurrency(calculateCost(usage)) }}
                    </span>
                </div>
                <div
                    v-if="index < normaliseUsage(analysisUsage).length - 1"
                    class="border-t border-indigo-200"
                />
            </div>

            <!-- Desktop: Table layout -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-indigo-200">
                            <th
                                class="px-2 py-2 text-left font-medium text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.model',
                                    )
                                }}
                            </th>
                            <th
                                class="w-28 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.inputTokens',
                                    )
                                }}
                            </th>
                            <th
                                class="w-28 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.outputTokens',
                                    )
                                }}
                            </th>
                            <th
                                class="w-24 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.total',
                                    )
                                }}
                            </th>
                            <th
                                v-if="hasCostData"
                                class="w-20 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.costHeader',
                                    )
                                }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(usage, index) in normaliseUsage(
                                analysisUsage,
                            )"
                            :key="`analysis-desktop-${index}`"
                        >
                            <td class="px-2 py-2 font-mono text-indigo-900">
                                {{ usage.model }}
                                <span
                                    v-if="
                                        normaliseUsage(analysisUsage).length > 1
                                    "
                                    class="ml-2 text-xs text-indigo-600"
                                >
                                    ({{
                                        $t(
                                            'promptBuilder.components.apiUsage.pass',
                                            {
                                                number: index + 1,
                                            },
                                        )
                                    }})
                                </span>
                            </td>
                            <td
                                class="w-28 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{ formatNumber(usage.input_tokens) }}
                            </td>
                            <td
                                class="w-28 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{ formatNumber(usage.output_tokens) }}
                            </td>
                            <td
                                class="w-24 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{ formatNumber(totalTokens(usage)) }}
                            </td>
                            <td
                                v-if="hasCostData"
                                class="w-20 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{
                                    calculateCost(usage) > 0
                                        ? formatCurrency(calculateCost(usage))
                                        : '—'
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Generation Workflow -->
        <div
            v-if="generationUsage"
            class="rounded-lg bg-indigo-50 p-4 dark:bg-indigo-100"
        >
            <h3 class="mb-3 text-sm font-medium text-indigo-900">
                {{ $t('promptBuilder.components.apiUsage.generation') }}
            </h3>
            <!-- Mobile: Stacked layout -->
            <div
                v-for="(usage, index) in normaliseUsage(generationUsage)"
                :key="`generation-mobile-${index}`"
                class="space-y-2 md:hidden"
            >
                <div
                    v-if="normaliseUsage(generationUsage).length > 1"
                    class="text-xs font-medium text-indigo-600"
                >
                    {{
                        $t('promptBuilder.components.apiUsage.pass', {
                            number: index + 1,
                        })
                    }}
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">
                        {{ $t('promptBuilder.components.apiUsage.modelLabel') }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{ usage.model }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">
                        {{
                            $t(
                                'promptBuilder.components.apiUsage.inputTokensLabel',
                            )
                        }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(usage.input_tokens) }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">
                        {{
                            $t(
                                'promptBuilder.components.apiUsage.outputTokensLabel',
                            )
                        }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(usage.output_tokens) }}
                    </span>
                </div>
                <div
                    class="flex justify-between border-t border-indigo-200 pt-2 text-sm font-medium"
                >
                    <span class="text-indigo-900">
                        {{ $t('promptBuilder.components.apiUsage.totalLabel') }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(totalTokens(usage)) }}
                    </span>
                </div>
                <div
                    v-if="hasCostData && calculateCost(usage) > 0"
                    class="flex justify-between text-sm"
                >
                    <span class="text-indigo-600">
                        {{ $t('promptBuilder.components.apiUsage.costLabel') }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{ formatCurrency(calculateCost(usage)) }}
                    </span>
                </div>
                <div
                    v-if="index < normaliseUsage(generationUsage).length - 1"
                    class="border-t border-indigo-200"
                />
            </div>

            <!-- Desktop: Table layout -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-indigo-200">
                            <th
                                class="px-2 py-2 text-left font-medium text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.model',
                                    )
                                }}
                            </th>
                            <th
                                class="w-28 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.inputTokens',
                                    )
                                }}
                            </th>
                            <th
                                class="w-28 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.outputTokens',
                                    )
                                }}
                            </th>
                            <th
                                class="w-24 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.total',
                                    )
                                }}
                            </th>
                            <th
                                v-if="hasCostData"
                                class="w-20 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.costHeader',
                                    )
                                }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(usage, index) in normaliseUsage(
                                generationUsage,
                            )"
                            :key="`generation-desktop-${index}`"
                        >
                            <td class="px-2 py-2 font-mono text-indigo-900">
                                {{ usage.model }}
                                <span
                                    v-if="
                                        normaliseUsage(generationUsage).length >
                                        1
                                    "
                                    class="ml-2 text-xs text-indigo-600"
                                >
                                    ({{
                                        $t(
                                            'promptBuilder.components.apiUsage.pass',
                                            {
                                                number: index + 1,
                                            },
                                        )
                                    }})
                                </span>
                            </td>
                            <td
                                class="w-28 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{ formatNumber(usage.input_tokens) }}
                            </td>
                            <td
                                class="w-28 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{ formatNumber(usage.output_tokens) }}
                            </td>
                            <td
                                class="w-24 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{ formatNumber(totalTokens(usage)) }}
                            </td>
                            <td
                                v-if="hasCostData"
                                class="w-20 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{
                                    calculateCost(usage) > 0
                                        ? formatCurrency(calculateCost(usage))
                                        : '—'
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Grand Total -->
        <div
            v-if="preAnalysisUsage || analysisUsage || generationUsage"
            class="rounded-lg bg-indigo-50 p-4 dark:bg-indigo-100"
        >
            <!-- Mobile: Stacked layout -->
            <div class="space-y-2 md:hidden">
                <div class="flex justify-between text-sm font-semibold">
                    <span class="text-indigo-900">
                        {{
                            $t(
                                'promptBuilder.components.apiUsage.combinedTotalLabel',
                            )
                        }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{
                            $t('promptBuilder.components.apiUsage.tokens', {
                                count: formatNumber(grandTotal()),
                            })
                        }}
                    </span>
                </div>
                <div
                    v-if="hasCostData && grandTotalCost > 0"
                    class="flex justify-between border-t border-indigo-200 pt-2 text-sm font-semibold"
                >
                    <span class="text-indigo-900">
                        {{
                            $t(
                                'promptBuilder.components.apiUsage.totalCostLabel',
                            )
                        }}
                    </span>
                    <span class="font-mono text-indigo-900">
                        {{ formatCurrency(grandTotalCost) }}
                    </span>
                </div>
            </div>

            <!-- Desktop: Table layout -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-indigo-200">
                            <th
                                class="px-2 py-2 text-left font-semibold text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.summary',
                                    )
                                }}
                            </th>
                            <th
                                class="w-28 px-2 py-2 text-right font-semibold text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.inputTokens',
                                    )
                                }}
                            </th>
                            <th
                                class="w-28 px-2 py-2 text-right font-semibold text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.outputTokens',
                                    )
                                }}
                            </th>
                            <th
                                class="w-24 px-2 py-2 text-right font-semibold text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.total',
                                    )
                                }}
                            </th>
                            <th
                                v-if="hasCostData"
                                class="w-20 px-2 py-2 text-right font-semibold text-indigo-900"
                            >
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.costHeader',
                                    )
                                }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t border-indigo-200">
                            <td class="px-2 py-2 font-semibold text-indigo-900">
                                {{
                                    $t(
                                        'promptBuilder.components.apiUsage.combinedTotal',
                                    )
                                }}
                            </td>
                            <td
                                class="w-28 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{
                                    formatNumber(
                                        normaliseUsage(preAnalysisUsage).reduce(
                                            (sum, u) => sum + u.input_tokens,
                                            0,
                                        ) +
                                            normaliseUsage(
                                                analysisUsage,
                                            ).reduce(
                                                (sum, u) =>
                                                    sum + u.input_tokens,
                                                0,
                                            ) +
                                            normaliseUsage(
                                                generationUsage,
                                            ).reduce(
                                                (sum, u) =>
                                                    sum + u.input_tokens,
                                                0,
                                            ),
                                    )
                                }}
                            </td>
                            <td
                                class="w-28 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{
                                    formatNumber(
                                        normaliseUsage(preAnalysisUsage).reduce(
                                            (sum, u) => sum + u.output_tokens,
                                            0,
                                        ) +
                                            normaliseUsage(
                                                analysisUsage,
                                            ).reduce(
                                                (sum, u) =>
                                                    sum + u.output_tokens,
                                                0,
                                            ) +
                                            normaliseUsage(
                                                generationUsage,
                                            ).reduce(
                                                (sum, u) =>
                                                    sum + u.output_tokens,
                                                0,
                                            ),
                                    )
                                }}
                            </td>
                            <td
                                class="w-24 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{ formatNumber(grandTotal()) }}
                            </td>
                            <td
                                v-if="hasCostData"
                                class="w-20 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{
                                    grandTotalCost > 0
                                        ? formatCurrency(grandTotalCost)
                                        : '—'
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- No Data -->
        <div
            v-if="!preAnalysisUsage && !analysisUsage && !generationUsage"
            class="text-center text-indigo-500"
        >
            {{ $t('promptBuilder.components.apiUsage.empty') }}
        </div>
    </Card>
</template>
