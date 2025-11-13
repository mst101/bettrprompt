<script setup lang="ts">
import ButtonText from '@/Components/ButtonText.vue';
import Card from '@/Components/Card.vue';
import type { PromptRunResource } from '@/types';

interface Progress {
    answered: number;
    total: number;
}

interface Props {
    promptRun: PromptRunResource;
    progress: Progress;
}

defineProps<Props>();

const emit = defineEmits<{
    (e: 'toggle-show-all'): void;
}>();

const isAnswered = (index: number, answers: (string | null)[] | null) => {
    return answers && answers[index] !== null && answers[index] !== undefined;
};

const isSkipped = (index: number, answers: (string | null)[] | null) => {
    return answers && answers[index] === null;
};
</script>

<template>
    <Card>
        <div class="space-y-6">
            <!-- Header with toggle link -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-700"
                        >All Questions</span
                    >
                    <ButtonText
                        id="toggle-question-view"
                        type="button"
                        :underline="true"
                        @click="emit('toggle-show-all')"
                    >
                        (one-at-a-time)
                    </ButtonText>
                </div>
                <span class="text-sm text-gray-500"
                    >{{ progress.answered }} of
                    {{ progress.total }} answered</span
                >
            </div>

            <!-- All questions list -->
            <div class="space-y-4">
                <div
                    v-for="(question, index) in promptRun.frameworkQuestions"
                    :key="index"
                    class="rounded-lg border border-gray-200 p-4"
                    :class="{
                        'border-green-200 bg-green-50': isAnswered(
                            index,
                            promptRun.clarifyingAnswers,
                        ),
                    }"
                >
                    <div class="mb-2 flex items-start">
                        <span
                            class="mr-2 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-medium text-indigo-800"
                        >
                            {{ index + 1 }}
                        </span>
                        <p class="text-sm font-medium text-gray-900">
                            {{ question }}
                        </p>
                    </div>
                    <div
                        v-if="isAnswered(index, promptRun.clarifyingAnswers)"
                        class="ml-8 text-sm text-gray-700"
                    >
                        <strong>Answer:</strong>
                        {{ promptRun.clarifyingAnswers![index] }}
                    </div>
                    <div
                        v-else-if="
                            isSkipped(index, promptRun.clarifyingAnswers)
                        "
                        class="ml-8 text-sm text-gray-500 italic"
                    >
                        Question skipped
                    </div>
                    <div v-else class="ml-8 text-sm text-gray-400 italic">
                        Not yet answered
                    </div>
                </div>
            </div>
        </div>
    </Card>
</template>
