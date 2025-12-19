<script setup lang="ts">
import type { ClarifyingQuestion } from '@/Components/Features/PromptBuilder/Cards/clarifyingQuestion';

defineProps<{
    questions: ClarifyingQuestion[];
    answers: (string | null)[] | null;
}>();
</script>

<template>
    <div v-if="answers && answers.length" class="space-y-3">
        <h3 class="text-sm font-semibold text-indigo-900">
            Your previous answers
        </h3>
        <div
            v-for="(question, index) in questions"
            :key="`answer-${question.id ?? index}`"
            class="rounded-md bg-indigo-50 p-4 shadow-xs dark:bg-indigo-100"
        >
            <div class="flex items-start gap-3">
                <span
                    class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white"
                >
                    {{ index + 1 }}
                </span>
                <div class="flex-1 space-y-2">
                    <p class="text-sm font-medium text-indigo-900">
                        {{ question.question }}
                    </p>
                    <p class="text-sm text-indigo-700">
                        {{ answers[index] ?? 'Skipped' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
