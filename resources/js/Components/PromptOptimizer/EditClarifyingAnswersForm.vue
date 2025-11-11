<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import type { PromptRunResource } from '@/types';
import { useForm } from '@inertiajs/vue3';

interface Props {
    promptRun: PromptRunResource;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'cancel'): void;
}>();

// Initialize form with existing answers (convert nulls to empty strings for editing)
const initialAnswers =
    props.promptRun.clarifyingAnswers?.map((answer) => answer ?? '') ?? [];

const form = useForm({
    clarifying_answers: initialAnswers,
});

const submit = () => {
    form.post(
        route('prompt-optimizer.create-child-from-answers', {
            parentPromptRun: props.promptRun.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                // Redirect happens automatically via controller
            },
        },
    );
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div class="space-y-4">
            <div
                v-for="(question, index) in promptRun.frameworkQuestions"
                :key="index"
                class="border-b border-gray-200 pb-4 last:border-b-0"
            >
                <div class="mb-2 flex items-start">
                    <span
                        class="mt-0.5 mr-2 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-medium text-indigo-800"
                    >
                        {{ index + 1 }}
                    </span>
                    <label
                        :for="`answer-${index}`"
                        class="block text-sm font-medium text-gray-900"
                    >
                        {{ question }}
                    </label>
                </div>

                <div class="ml-8">
                    <FormTextarea
                        :id="`answer-${index}`"
                        label=""
                        v-model="form.clarifying_answers[index]"
                        :rows="3"
                        :error="
                            (form.errors as Record<string, string>)[
                                `clarifying_answers.${index}`
                            ]
                        "
                        placeholder="Your answer (leave empty to skip)..."
                    />
                </div>
            </div>
        </div>

        <div
            class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4"
        >
            <ButtonSecondary
                type="button"
                @click="emit('cancel')"
                :disabled="form.processing"
            >
                Cancel
            </ButtonSecondary>
            <ButtonPrimary type="submit" :loading="form.processing">
                Optimise Prompt with Edited Answers
            </ButtonPrimary>
        </div>
    </form>
</template>
