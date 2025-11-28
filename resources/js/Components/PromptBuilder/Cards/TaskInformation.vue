<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import Card from '@/Components/Card.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import { useTextAppend } from '@/Composables/useTextAppend';
import type { PromptRunResource } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Props {
    promptRun: PromptRunResource;
}

const props = defineProps<Props>();

const isEditing = ref(false);
const { appendText } = useTextAppend();
const form = useForm({
    task_description: props.promptRun.taskDescription,
});

const startEditing = () => {
    isEditing.value = true;
};

const cancelEditing = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
};

const handleTranscription = (transcript: string) => {
    form.task_description = appendText(form.task_description, transcript);
};

const submit = () => {
    form.post(
        route('prompt-builder.create-child', {
            parentPromptRun: props.promptRun.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                // Redirect handled server-side
            },
        },
    );
};

// Reset edit mode when navigating to different prompt run
watch(
    () => props.promptRun.id,
    () => {
        isEditing.value = false;
        form.task_description = props.promptRun.taskDescription;
        form.clearErrors();
    },
);
</script>

<template>
    <Card>
        <div class="flex items-start justify-between gap-4">
            <h2 class="text-lg font-semibold text-gray-900">
                {{
                    isEditing
                        ? 'Edit Task & Create New Optimisation'
                        : 'Your Task'
                }}
            </h2>

            <ButtonSecondary
                v-if="!isEditing"
                type="button"
                class="inline-flex items-center gap-1"
                @click="startEditing"
            >
                <DynamicIcon name="edit" class="h-4 w-4" />
                Edit Task
            </ButtonSecondary>

            <ButtonSecondary
                v-else
                type="button"
                :disabled="form.processing"
                @click="cancelEditing"
            >
                Cancel
            </ButtonSecondary>
        </div>

        <!-- View Mode -->
        <FormTextarea
            v-if="!isEditing"
            id="task-description"
            :model-value="promptRun.taskDescription"
            label="Task Description"
            :sr-only-label="true"
            :disabled="true"
            required
            :rows="4"
        />

        <!-- Edit Mode -->
        <form v-else @submit.prevent="submit">
            <FormTextarea
                id="task_description"
                v-model="form.task_description"
                label="Edit Task Description"
                :sr-only-label="true"
                :rows="4"
                :error="form.errors.task_description"
                required
                autofocus
                placeholder="Describe the task you want to create a prompt for..."
            />

            <div class="mt-4 flex items-center justify-between">
                <ButtonVoiceInput @transcription="handleTranscription" />

                <ButtonPrimary
                    type="submit"
                    :disabled="
                        !form.task_description ||
                        form.task_description.trim() === '' ||
                        form.processing
                    "
                    :loading="form.processing"
                >
                    Optimise Prompt
                </ButtonPrimary>
            </div>
        </form>
    </Card>
</template>
