<script setup lang="ts">
import Card from '@/Components/Card.vue';

interface ApiUsageData {
    model: string;
    input_tokens: number;
    output_tokens: number;
}

interface Props {
    analysisUsage: ApiUsageData | null;
    generationUsage: ApiUsageData | null;
}

const props = defineProps<Props>();

const totalTokens = (usage: ApiUsageData | null) => {
    if (!usage) return 0;
    return usage.input_tokens + usage.output_tokens;
};

const formatNumber = (num: number) => {
    return num.toLocaleString();
};

const grandTotal = () => {
    return (
        totalTokens(props.analysisUsage) + totalTokens(props.generationUsage)
    );
};
</script>

<template>
    <Card class="space-y-6">
        <h2 class="text-lg font-semibold text-gray-900">API Usage</h2>

        <!-- Analysis Workflow -->
        <div v-if="analysisUsage" class="rounded-lg border border-gray-200 p-4">
            <h3 class="mb-3 text-sm font-medium text-gray-700">
                Analysis Workflow
            </h3>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Model:</span>
                    <span class="font-mono text-gray-900">
                        {{ analysisUsage.model }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Input Tokens:</span>
                    <span class="font-mono text-gray-900">
                        {{ formatNumber(analysisUsage.input_tokens) }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Output Tokens:</span>
                    <span class="font-mono text-gray-900">
                        {{ formatNumber(analysisUsage.output_tokens) }}
                    </span>
                </div>
                <div
                    class="flex justify-between border-t border-gray-200 pt-2 text-sm font-medium"
                >
                    <span class="text-gray-900">Total:</span>
                    <span class="font-mono text-gray-900">
                        {{ formatNumber(totalTokens(analysisUsage)) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Generation Workflow -->
        <div
            v-if="generationUsage"
            class="rounded-lg border border-gray-200 p-4"
        >
            <h3 class="mb-3 text-sm font-medium text-gray-700">
                Generation Workflow
            </h3>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Model:</span>
                    <span class="font-mono text-gray-900">
                        {{ generationUsage.model }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Input Tokens:</span>
                    <span class="font-mono text-gray-900">
                        {{ formatNumber(generationUsage.input_tokens) }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Output Tokens:</span>
                    <span class="font-mono text-gray-900">
                        {{ formatNumber(generationUsage.output_tokens) }}
                    </span>
                </div>
                <div
                    class="flex justify-between border-t border-gray-200 pt-2 text-sm font-medium"
                >
                    <span class="text-gray-900">Total:</span>
                    <span class="font-mono text-gray-900">
                        {{ formatNumber(totalTokens(generationUsage)) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Grand Total -->
        <div
            v-if="analysisUsage || generationUsage"
            class="rounded-lg bg-indigo-50 p-4"
        >
            <div class="flex justify-between text-sm font-semibold">
                <span class="text-indigo-900">Combined Total:</span>
                <span class="font-mono text-indigo-900">
                    {{ formatNumber(grandTotal()) }} tokens
                </span>
            </div>
        </div>

        <!-- No Data -->
        <div
            v-if="!analysisUsage && !generationUsage"
            class="text-center text-gray-500"
        >
            No API usage data available
        </div>
    </Card>
</template>
