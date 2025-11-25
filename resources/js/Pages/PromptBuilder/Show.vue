<script setup lang="ts">
import ContainerPage from '@/Components/ContainerPage.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

interface ClarifyingQuestion {
    id: string;
    question: string;
    purpose: string;
    required: boolean;
}

interface AnalysisData {
    task_classification: {
        primary_category: string;
        secondary_category: string | null;
        complexity: string;
        classification_reasoning: string;
    };
    selected_framework: {
        name: string;
        code: string;
        components: string[];
        rationale: string;
    };
    alternative_frameworks: Array<{
        name: string;
        code: string;
        when_to_use_instead: string;
    }>;
    personality_tier: 'full' | 'partial' | 'none';
    personality_adjustments_preview: string[];
    clarifying_questions: ClarifyingQuestion[];
    question_rationale: string;
}

interface Props {
    analysis: {
        success: boolean;
        data: AnalysisData;
        original_input: {
            task_description: string;
            personality_type: string | null;
            trait_percentages: Record<string, number> | null;
        };
        error: {
            message: string;
            details: string;
        } | null;
    };
}

const taskDescription = computed(
    () => props.analysis.original_input.task_description,
);
const analysisData = computed(() => props.analysis.data);
</script>

<template>
    <Head title="Prompt Analysis" />

    <ContainerPage>
        <HeaderPage
            title="Prompt Analysis Results"
            :description="`Analysis for: ${taskDescription}`"
        />

        <!-- Task Classification -->
        <div
            class="border-grey-200 mb-6 rounded-lg border bg-white p-6 shadow-sm"
        >
            <h2 class="text-grey-900 mb-3 text-lg font-semibold">
                Task Classification
            </h2>
            <div class="space-y-2">
                <div>
                    <span class="text-grey-700 font-medium">Category:</span>
                    <span class="text-grey-900 ml-2">
                        {{ analysisData.task_classification.primary_category }}
                    </span>
                </div>
                <div>
                    <span class="text-grey-700 font-medium">Complexity:</span>
                    <span class="text-grey-900 ml-2 capitalize">
                        {{ analysisData.task_classification.complexity }}
                    </span>
                </div>
                <div>
                    <span class="text-grey-700 font-medium">Reasoning:</span>
                    <p class="text-grey-900 mt-1">
                        {{
                            analysisData.task_classification
                                .classification_reasoning
                        }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Selected Framework -->
        <div
            class="border-grey-200 mb-6 rounded-lg border bg-white p-6 shadow-sm"
        >
            <h2 class="text-grey-900 mb-3 text-lg font-semibold">
                Selected Framework
            </h2>
            <div class="space-y-2">
                <div>
                    <span class="text-grey-700 font-medium">Framework:</span>
                    <span class="text-grey-900 ml-2">
                        {{ analysisData.selected_framework.name }}
                        ({{ analysisData.selected_framework.code }})
                    </span>
                </div>
                <div>
                    <span class="text-grey-700 font-medium">Components:</span>
                    <div class="mt-1 flex flex-wrap gap-2">
                        <span
                            v-for="component in analysisData.selected_framework
                                .components"
                            :key="component"
                            class="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800"
                        >
                            {{ component }}
                        </span>
                    </div>
                </div>
                <div>
                    <span class="text-grey-700 font-medium">Rationale:</span>
                    <p class="text-grey-900 mt-1">
                        {{ analysisData.selected_framework.rationale }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Personality Adjustments -->
        <div
            v-if="analysisData.personality_tier !== 'none'"
            class="border-grey-200 mb-6 rounded-lg border bg-white p-6 shadow-sm"
        >
            <h2 class="text-grey-900 mb-3 text-lg font-semibold">
                Personality Adjustments ({{ analysisData.personality_tier }})
            </h2>
            <ul class="text-grey-900 list-inside list-disc space-y-1">
                <li
                    v-for="(
                        adjustment, index
                    ) in analysisData.personality_adjustments_preview"
                    :key="index"
                >
                    {{ adjustment }}
                </li>
            </ul>
        </div>

        <!-- Clarifying Questions -->
        <div
            class="border-grey-200 mb-6 rounded-lg border bg-white p-6 shadow-sm"
        >
            <h2 class="text-grey-900 mb-3 text-lg font-semibold">
                Clarifying Questions
            </h2>
            <p class="text-grey-600 mb-4 text-sm">
                {{ analysisData.question_rationale }}
            </p>
            <div class="space-y-4">
                <div
                    v-for="question in analysisData.clarifying_questions"
                    :key="question.id"
                    class="border-grey-200 bg-grey-50 rounded-lg border p-4"
                >
                    <div class="mb-2 flex items-start justify-between">
                        <h3 class="text-grey-900 font-medium">
                            {{ question.question }}
                            <span
                                v-if="question.required"
                                class="ml-1 text-red-500"
                                title="Required"
                            >
                                *
                            </span>
                        </h3>
                    </div>
                    <p class="text-grey-600 text-sm">
                        <span class="font-medium">Purpose:</span>
                        {{ question.purpose }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Alternative Frameworks -->
        <div
            v-if="analysisData.alternative_frameworks.length > 0"
            class="border-grey-200 mb-6 rounded-lg border bg-white p-6 shadow-sm"
        >
            <h2 class="text-grey-900 mb-3 text-lg font-semibold">
                Alternative Frameworks
            </h2>
            <div class="space-y-3">
                <div
                    v-for="framework in analysisData.alternative_frameworks"
                    :key="framework.code"
                    class="border-grey-200 bg-grey-50 rounded border p-3"
                >
                    <div class="text-grey-900 font-medium">
                        {{ framework.name }} ({{ framework.code }})
                    </div>
                    <div class="text-grey-600 mt-1 text-sm">
                        When to use: {{ framework.when_to_use_instead }}
                    </div>
                </div>
            </div>
        </div>
    </ContainerPage>
</template>
