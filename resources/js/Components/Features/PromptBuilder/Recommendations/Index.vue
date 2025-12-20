<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import QuestionNumber from '@/Components/Features/PromptBuilder/QuestionNumber.vue';

interface ModelRecommendation {
    rank: number;
    model: string;
    model_id: string;
    rationale: string;
}

interface Props {
    modelRecommendations: ModelRecommendation[] | null;
    iterationSuggestions: string[] | null;
}

defineProps<Props>();
</script>

<template>
    <Card class="space-y-6">
        <h2 class="text-lg font-semibold text-indigo-900">Recommendations</h2>

        <!-- Model Recommendations -->
        <div v-if="modelRecommendations && modelRecommendations.length > 0">
            <h3 class="mb-3 text-sm font-medium text-indigo-700">
                Recommended AI Models
            </h3>
            <div class="space-y-3">
                <div
                    v-for="rec in modelRecommendations"
                    :key="rec.rank"
                    class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:bg-indigo-100"
                >
                    <div class="mb-2 flex items-start justify-between">
                        <div class="flex items-center gap-2">
                            <QuestionNumber class="mr-1" :number="rec.rank" />
                            <div>
                                <h4 class="font-medium text-indigo-900">
                                    {{ rec.model }}
                                </h4>
                                <p class="font-mono text-xs text-indigo-600">
                                    {{ rec.model_id }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <p class="text-sm text-indigo-700">
                        {{ rec.rationale }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Iteration Suggestions -->
        <div
            v-if="iterationSuggestions && iterationSuggestions.length > 0"
            class="mt-8 rounded-lg border border-blue-200 bg-blue-50 p-4"
        >
            <h3 class="mb-3 text-sm font-medium text-blue-900">
                Iteration Suggestions
            </h3>
            <p class="mb-3 text-sm text-blue-800">
                If the results aren't quite what you need, try these
                refinements:
            </p>
            <ul class="space-y-2">
                <li
                    v-for="(suggestion, index) in iterationSuggestions"
                    :key="index"
                    class="flex items-start text-sm text-blue-900"
                >
                    <svg
                        class="mt-0.5 mr-2 h-5 w-5 flex-shrink-0 text-blue-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 7l5 5m0 0l-5 5m5-5H6"
                        />
                    </svg>
                    {{ suggestion }}
                </li>
            </ul>
        </div>

        <!-- No Data -->
        <div
            v-if="
                (!modelRecommendations || modelRecommendations.length === 0) &&
                (!iterationSuggestions || iterationSuggestions.length === 0)
            "
            class="text-center text-indigo-500"
        >
            No recommendations available
        </div>
    </Card>
</template>
