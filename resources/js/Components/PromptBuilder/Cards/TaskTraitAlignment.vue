<script setup lang="ts">
import Card from '@/Components/Card.vue';

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
        <h2 class="text-lg font-semibold text-gray-900">
            Task-Trait Alignment
        </h2>

        <!-- Amplified Traits -->
        <div v-if="alignment.amplified && alignment.amplified.length > 0">
            <h3 class="mb-3 text-sm font-medium text-green-700">
                Amplified Traits (Strengths Leveraged)
            </h3>
            <div class="space-y-3">
                <div
                    v-for="(item, index) in alignment.amplified"
                    :key="index"
                    class="rounded-lg border border-green-200 bg-green-50 p-4"
                >
                    <div class="mb-2 flex items-start justify-between">
                        <div>
                            <span
                                class="inline-block rounded-full bg-green-600 px-2 py-0.5 text-xs font-medium text-white"
                            >
                                {{ item.trait }}
                            </span>
                            <span
                                class="ml-2 text-sm font-medium text-gray-900"
                            >
                                aligns with {{ item.requirement_aligned }}
                            </span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-700">
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
            <h3 class="mb-3 text-sm font-medium text-orange-700">
                Counterbalanced Traits (Adjusted For)
            </h3>
            <div class="space-y-3">
                <div
                    v-for="(item, index) in alignment.counterbalanced"
                    :key="index"
                    class="rounded-lg border border-orange-200 bg-orange-50 p-4"
                >
                    <div class="mb-2">
                        <span
                            class="inline-block rounded-full bg-orange-600 px-2 py-0.5 text-xs font-medium text-white"
                        >
                            {{ item.trait }}
                        </span>
                        <span class="ml-2 text-sm font-medium text-gray-900">
                            opposes {{ item.requirement_opposed }}
                        </span>
                    </div>
                    <p class="mb-2 text-sm text-gray-700">
                        {{ item.reason }}
                    </p>
                    <div
                        class="rounded border-l-4 border-orange-400 bg-white p-3"
                    >
                        <p class="text-xs font-medium text-orange-800">
                            Adjustment Applied:
                        </p>
                        <p class="mt-1 text-sm text-gray-700">
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
                    class="cursor-pointer text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                    <span
                        class="inline-block transition-transform group-open:rotate-90"
                    >
                        ▶
                    </span>
                    Neutral Traits ({{ alignment.neutral.length }})
                </summary>
                <div class="mt-3 space-y-2">
                    <div
                        v-for="(item, index) in alignment.neutral"
                        :key="index"
                        class="rounded-lg border border-gray-200 bg-gray-50 p-3"
                    >
                        <span
                            class="inline-block rounded-full bg-gray-400 px-2 py-0.5 text-xs font-medium text-white"
                        >
                            {{ item.trait }}
                        </span>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ item.reason }}
                        </p>
                    </div>
                </div>
            </details>
        </div>
    </Card>
</template>
