<script setup lang="ts">
import Card from '@/Components/Card.vue';
import type { ClaudeModel } from '@/types/models';
import { computed } from 'vue';

interface ApiUsageData {
    model: string;
    input_tokens: number;
    output_tokens: number;
}

interface Props {
    preAnalysisUsage: ApiUsageData | null;
    analysisUsage: ApiUsageData | null;
    generationUsage: ApiUsageData | null;
    claudeModels?: ClaudeModel[];
}

const props = withDefaults(defineProps<Props>(), {
    claudeModels: () => [],
});

const totalTokens = (usage: ApiUsageData | null) => {
    if (!usage) return 0;
    return usage.input_tokens + usage.output_tokens;
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

const calculateCost = (usage: ApiUsageData | null) => {
    if (!usage) return 0;

    const model = getModelPricing(usage.model);
    if (!model) return 0;

    const inputCost = (usage.input_tokens / 1_000_000) * model.inputCostPerMtok;
    const outputCost =
        (usage.output_tokens / 1_000_000) * model.outputCostPerMtok;

    return inputCost + outputCost;
};

const grandTotal = () => {
    return (
        totalTokens(props.preAnalysisUsage) +
        totalTokens(props.analysisUsage) +
        totalTokens(props.generationUsage)
    );
};

const grandTotalCost = computed(() => {
    return (
        calculateCost(props.preAnalysisUsage) +
        calculateCost(props.analysisUsage) +
        calculateCost(props.generationUsage)
    );
});

const hasCostData = computed(() => {
    return props.claudeModels && props.claudeModels.length > 0;
});
</script>

<template>
    <Card class="space-y-6">
        <h2 class="text-lg font-semibold text-indigo-900">API Usage</h2>

        <!-- Pre-Analysis Workflow -->
        <div
            v-if="preAnalysisUsage"
            class="rounded-lg bg-indigo-50 p-4 dark:bg-indigo-100"
        >
            <h3 class="mb-3 text-sm font-medium text-indigo-900">
                Pre-Analysis Workflow
            </h3>
            <!-- Mobile: Stacked layout -->
            <div class="space-y-2 md:hidden">
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">Model:</span>
                    <span class="font-mono text-indigo-900">
                        {{ preAnalysisUsage.model }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">Input Tokens:</span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(preAnalysisUsage.input_tokens) }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">Output Tokens:</span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(preAnalysisUsage.output_tokens) }}
                    </span>
                </div>
                <div
                    class="flex justify-between border-t border-indigo-200 pt-2 text-sm font-medium"
                >
                    <span class="text-indigo-900">Total:</span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(totalTokens(preAnalysisUsage)) }}
                    </span>
                </div>
                <div
                    v-if="hasCostData && calculateCost(preAnalysisUsage) > 0"
                    class="flex justify-between text-sm"
                >
                    <span class="text-indigo-600">Cost:</span>
                    <span class="font-mono text-indigo-900">
                        {{ formatCurrency(calculateCost(preAnalysisUsage)) }}
                    </span>
                </div>
            </div>

            <!-- Desktop: Table layout -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-indigo-200">
                            <th
                                class="px-2 py-2 text-left font-medium text-indigo-900"
                            >
                                Model
                            </th>
                            <th
                                class="w-28 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                Input Tokens
                            </th>
                            <th
                                class="w-28 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                Output Tokens
                            </th>
                            <th
                                class="w-24 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                Total
                            </th>
                            <th
                                v-if="hasCostData"
                                class="w-20 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                Cost (£)
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-2 py-2 font-mono text-indigo-900">
                                {{ preAnalysisUsage.model }}
                            </td>
                            <td
                                class="w-28 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{
                                    formatNumber(preAnalysisUsage.input_tokens)
                                }}
                            </td>
                            <td
                                class="w-28 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{
                                    formatNumber(preAnalysisUsage.output_tokens)
                                }}
                            </td>
                            <td
                                class="w-24 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{
                                    formatNumber(totalTokens(preAnalysisUsage))
                                }}
                            </td>
                            <td
                                v-if="hasCostData"
                                class="w-20 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{
                                    calculateCost(preAnalysisUsage) > 0
                                        ? formatCurrency(
                                              calculateCost(preAnalysisUsage),
                                          )
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
                Analysis Workflow
            </h3>
            <!-- Mobile: Stacked layout -->
            <div class="space-y-2 md:hidden">
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">Model:</span>
                    <span class="font-mono text-indigo-900">
                        {{ analysisUsage.model }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">Input Tokens:</span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(analysisUsage.input_tokens) }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">Output Tokens:</span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(analysisUsage.output_tokens) }}
                    </span>
                </div>
                <div
                    class="flex justify-between border-t border-indigo-200 pt-2 text-sm font-medium"
                >
                    <span class="text-indigo-900">Total:</span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(totalTokens(analysisUsage)) }}
                    </span>
                </div>
                <div
                    v-if="hasCostData && calculateCost(analysisUsage) > 0"
                    class="flex justify-between text-sm"
                >
                    <span class="text-indigo-600">Cost:</span>
                    <span class="font-mono text-indigo-900">
                        {{ formatCurrency(calculateCost(analysisUsage)) }}
                    </span>
                </div>
            </div>

            <!-- Desktop: Table layout -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-indigo-200">
                            <th
                                class="px-2 py-2 text-left font-medium text-indigo-900"
                            >
                                Model
                            </th>
                            <th
                                class="w-28 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                Input Tokens
                            </th>
                            <th
                                class="w-28 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                Output Tokens
                            </th>
                            <th
                                class="w-24 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                Total
                            </th>
                            <th
                                v-if="hasCostData"
                                class="w-20 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                Cost (£)
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-2 py-2 font-mono text-indigo-900">
                                {{ analysisUsage.model }}
                            </td>
                            <td
                                class="w-28 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{ formatNumber(analysisUsage.input_tokens) }}
                            </td>
                            <td
                                class="w-28 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{ formatNumber(analysisUsage.output_tokens) }}
                            </td>
                            <td
                                class="w-24 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{ formatNumber(totalTokens(analysisUsage)) }}
                            </td>
                            <td
                                v-if="hasCostData"
                                class="w-20 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{
                                    calculateCost(analysisUsage) > 0
                                        ? formatCurrency(
                                              calculateCost(analysisUsage),
                                          )
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
                Generation Workflow
            </h3>
            <!-- Mobile: Stacked layout -->
            <div class="space-y-2 md:hidden">
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">Model:</span>
                    <span class="font-mono text-indigo-900">
                        {{ generationUsage.model }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">Input Tokens:</span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(generationUsage.input_tokens) }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-600">Output Tokens:</span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(generationUsage.output_tokens) }}
                    </span>
                </div>
                <div
                    class="flex justify-between border-t border-indigo-200 pt-2 text-sm font-medium"
                >
                    <span class="text-indigo-900">Total:</span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(totalTokens(generationUsage)) }}
                    </span>
                </div>
                <div
                    v-if="hasCostData && calculateCost(generationUsage) > 0"
                    class="flex justify-between text-sm"
                >
                    <span class="text-indigo-600">Cost:</span>
                    <span class="font-mono text-indigo-900">
                        {{ formatCurrency(calculateCost(generationUsage)) }}
                    </span>
                </div>
            </div>

            <!-- Desktop: Table layout -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-indigo-200">
                            <th
                                class="px-2 py-2 text-left font-medium text-indigo-900"
                            >
                                Model
                            </th>
                            <th
                                class="w-28 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                Input Tokens
                            </th>
                            <th
                                class="w-28 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                Output Tokens
                            </th>
                            <th
                                class="w-24 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                Total
                            </th>
                            <th
                                v-if="hasCostData"
                                class="w-20 px-2 py-2 text-right font-medium text-indigo-900"
                            >
                                Cost (£)
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-2 py-2 font-mono text-indigo-900">
                                {{ generationUsage.model }}
                            </td>
                            <td
                                class="w-28 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{ formatNumber(generationUsage.input_tokens) }}
                            </td>
                            <td
                                class="w-28 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{
                                    formatNumber(generationUsage.output_tokens)
                                }}
                            </td>
                            <td
                                class="w-24 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{ formatNumber(totalTokens(generationUsage)) }}
                            </td>
                            <td
                                v-if="hasCostData"
                                class="w-20 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{
                                    calculateCost(generationUsage) > 0
                                        ? formatCurrency(
                                              calculateCost(generationUsage),
                                          )
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
                    <span class="text-indigo-900">Combined Total:</span>
                    <span class="font-mono text-indigo-900">
                        {{ formatNumber(grandTotal()) }} tokens
                    </span>
                </div>
                <div
                    v-if="hasCostData && grandTotalCost > 0"
                    class="flex justify-between border-t border-indigo-200 pt-2 text-sm font-semibold"
                >
                    <span class="text-indigo-900">Total Cost:</span>
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
                                Summary
                            </th>
                            <th
                                class="w-28 px-2 py-2 text-right font-semibold text-indigo-900"
                            >
                                Input Tokens
                            </th>
                            <th
                                class="w-28 px-2 py-2 text-right font-semibold text-indigo-900"
                            >
                                Output Tokens
                            </th>
                            <th
                                class="w-24 px-2 py-2 text-right font-semibold text-indigo-900"
                            >
                                Total
                            </th>
                            <th
                                v-if="hasCostData"
                                class="w-20 px-2 py-2 text-right font-semibold text-indigo-900"
                            >
                                Cost (£)
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t border-indigo-200">
                            <td class="px-2 py-2 font-semibold text-indigo-900">
                                Combined Total
                            </td>
                            <td
                                class="w-28 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{
                                    formatNumber(
                                        (preAnalysisUsage?.input_tokens || 0) +
                                            (analysisUsage?.input_tokens || 0) +
                                            (generationUsage?.input_tokens ||
                                                0),
                                    )
                                }}
                            </td>
                            <td
                                class="w-28 px-2 py-2 text-right font-mono text-indigo-900"
                            >
                                {{
                                    formatNumber(
                                        (preAnalysisUsage?.output_tokens || 0) +
                                            (analysisUsage?.output_tokens ||
                                                0) +
                                            (generationUsage?.output_tokens ||
                                                0),
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
            No API usage data available
        </div>
    </Card>
</template>
