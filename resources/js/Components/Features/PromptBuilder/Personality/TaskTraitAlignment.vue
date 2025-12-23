<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';

interface AmplifiedTrait {
    trait: string;
    requirement_aligned: string;
    reason: string;
}

interface CounterbalancedTrait {
    trait: string;
    requirement_opposed: string;
    reason: string;
    injection: string;
}

interface NeutralTrait {
    trait: string;
    reason: string;
}

interface Props {
    alignment: {
        amplified: AmplifiedTrait[];
        counterbalanced: CounterbalancedTrait[];
        neutral: NeutralTrait[];
    };
}

defineProps<Props>();
</script>

<template>
    <Card class="space-y-6">
        <h2 class="text-lg font-semibold text-indigo-900">
            Task-Trait Alignment
        </h2>

        <!-- Amplified Traits -->
        <div v-if="alignment.amplified && alignment.amplified.length > 0">
            <h3 class="mb-3 text-sm font-medium text-blue-700">
                Amplified Traits (Strengths Leveraged)
            </h3>
            <div class="space-y-3">
                <div
                    v-for="(item, index) in alignment.amplified"
                    :key="index"
                    class="rounded-lg border border-blue-200 bg-blue-100 p-4"
                >
                    <div class="mb-2 flex items-start justify-between">
                        <div>
                            <span
                                class="inline-block rounded-full bg-blue-600 px-2 py-0.5 text-xs font-medium text-white"
                            >
                                {{ item.trait }}
                            </span>
                            <span
                                class="ml-2 text-sm font-medium text-blue-900"
                            >
                                aligns with {{ item.requirement_aligned }}
                            </span>
                        </div>
                    </div>
                    <p class="text-sm text-blue-800">
                        {{ item.reason }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Counterbalanced Traits -->
        <div
            v-if="
                alignment.counterbalanced &&
                alignment.counterbalanced.length > 0
            "
        >
            <h3 class="mb-3 text-sm font-medium text-purple-700">
                Counterbalanced Traits (Adjusted For)
            </h3>
            <div class="space-y-3">
                <div
                    v-for="(item, index) in alignment.counterbalanced"
                    :key="index"
                    class="rounded-lg border border-purple-200 bg-purple-50 p-4"
                >
                    <div class="mb-2">
                        <span
                            class="inline-block rounded-full bg-purple-600 px-2 py-0.5 text-xs font-medium text-white"
                        >
                            {{ item.trait }}
                        </span>
                        <span class="ml-2 text-sm font-medium text-indigo-900">
                            opposes {{ item.requirement_opposed }}
                        </span>
                    </div>
                    <p class="mb-2 text-sm text-purple-700">
                        {{ item.reason }}
                    </p>
                    <div
                        class="rounded border-l-4 border-purple-400 bg-purple-100 p-3"
                    >
                        <p class="text-xs font-medium text-purple-800">
                            Adjustment Applied:
                        </p>
                        <p class="mt-1 text-sm text-purple-950">
                            {{ item.injection }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Neutral Traits -->
        <div v-if="alignment.neutral && alignment.neutral.length > 0">
            <details class="group">
                <summary
                    class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-700"
                >
                    Neutral Traits ({{ alignment.neutral.length }})
                </summary>
                <div class="mt-3 space-y-2">
                    <div
                        v-for="(item, index) in alignment.neutral"
                        :key="index"
                        class="rounded-lg border border-indigo-100 bg-indigo-50 p-3"
                    >
                        <span
                            class="inline-block rounded-full bg-indigo-400 px-2 py-0.5 text-xs font-medium text-white"
                        >
                            {{ item.trait }}
                        </span>
                        <p class="mt-1 text-sm text-indigo-600">
                            {{ item.reason }}
                        </p>
                    </div>
                </div>
            </details>
        </div>
    </Card>
</template>
