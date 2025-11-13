<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';
import type { PromptRunResource } from '@/types';
import { ref } from 'vue';

interface Props {
    promptRun: PromptRunResource;
}

defineProps<Props>();

const emit = defineEmits<{
    (e: 'edit'): void;
}>();

// Collapsible Q&A state for answered questions
const expandedQuestions = ref<Set<number>>(new Set());

const toggleQuestion = (index: number) => {
    if (expandedQuestions.value.has(index)) {
        expandedQuestions.value.delete(index);
    } else {
        expandedQuestions.value.add(index);
    }
};

const allExpanded = (totalQuestions: number) => {
    return (
        totalQuestions > 0 && expandedQuestions.value.size === totalQuestions
    );
};

const toggleAll = (totalQuestions: number) => {
    if (allExpanded(totalQuestions)) {
        expandedQuestions.value.clear();
    } else {
        for (let i = 0; i < totalQuestions; i++) {
            expandedQuestions.value.add(i);
        }
    }
};
</script>

<template>
    <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">
                    Clarifying Questions
                </h3>
                <div class="flex items-center gap-2">
                    <button
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        @click="
                            toggleAll(promptRun.frameworkQuestions?.length ?? 0)
                        "
                    >
                        {{
                            allExpanded(
                                promptRun.frameworkQuestions?.length ?? 0,
                            )
                                ? 'Hide All'
                                : 'Show All'
                        }}
                    </button>
                    <button
                        class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        @click="emit('edit')"
                    >
                        <DynamicIcon name="edit" class="h-4 w-4" />
                        Edit Answers
                    </button>
                </div>
            </div>

            <div class="space-y-3">
                <div
                    v-for="(question, index) in promptRun.frameworkQuestions"
                    :key="index"
                    class="border-b border-gray-200 pb-3 last:border-b-0"
                >
                    <div
                        role="button"
                        tabindex="0"
                        class="flex w-full cursor-pointer items-start justify-between rounded-md p-1 text-left focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        :aria-label="
                            expandedQuestions.has(index)
                                ? `Hide question ${index + 1}`
                                : `Show question ${index + 1}`
                        "
                        :aria-expanded="expandedQuestions.has(index)"
                        @click="toggleQuestion(index)"
                        @keydown.enter="toggleQuestion(index)"
                        @keydown.space.prevent="toggleQuestion(index)"
                    >
                        <div class="flex-1">
                            <div class="flex items-start">
                                <span
                                    class="mt-0.5 mr-2 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-medium text-indigo-800"
                                >
                                    {{ index + 1 }}
                                </span>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ question }}
                                </p>
                            </div>
                        </div>
                        <DynamicIcon
                            name="chevron-down"
                            :class="[
                                'ml-4 h-5 w-5 shrink-0 text-gray-400 transition-transform',
                                expandedQuestions.has(index)
                                    ? 'rotate-180'
                                    : '',
                            ]"
                        />
                    </div>

                    <div
                        v-show="expandedQuestions.has(index)"
                        class="mt-2 ml-8"
                    >
                        <div
                            v-if="
                                promptRun.clarifyingAnswers &&
                                promptRun.clarifyingAnswers[index] !== null &&
                                promptRun.clarifyingAnswers[index] !== undefined
                            "
                            class="rounded-md bg-gray-50 p-3"
                        >
                            <p
                                class="text-sm whitespace-break-spaces text-gray-700"
                            >
                                {{ promptRun.clarifyingAnswers[index] }}
                            </p>
                        </div>
                        <div v-else class="rounded-md bg-gray-50 p-3">
                            <p class="text-sm text-gray-500 italic">
                                [Skipped]
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
