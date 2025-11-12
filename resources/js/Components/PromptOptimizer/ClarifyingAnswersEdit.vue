<script setup lang="ts">
import FormTextarea from '@/Components/FormTextarea.vue';
import type { PromptRunResource } from '@/types';
import type { InertiaForm } from '@inertiajs/vue3';

interface Props {
    promptRun: PromptRunResource;
    form: InertiaForm<{ clarifying_answers: string[] }>;
}

defineProps<Props>();
</script>

<!-- eslint-disable vue/no-mutating-props -->
<template>
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
</template>
