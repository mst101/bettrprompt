<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import Card from '@/Components/Card.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import VisitorLimitModal from '@/Components/VisitorLimitModal.vue';
import { useTextAppend } from '@/Composables/useTextAppend';
import type { PromptRunResource } from '@/types';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, inject, ref, watch, watchEffect } from 'vue';

interface Props {
    promptRun: PromptRunResource;
    visitorHasCompletedPrompts?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    visitorHasCompletedPrompts: false,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const openRegisterModal = inject<() => void>('openRegisterModal');
const showVisitorLimitModal = ref(false);

const isEditing = ref(false);
const shouldFocusTextarea = ref(false);
const shouldFocusButton = ref(false);
const taskTextareaRef = ref<InstanceType<typeof FormTextarea> | null>(null);
const editButtonRef = ref<InstanceType<typeof ButtonSecondary> | null>(null);
const { appendText } = useTextAppend();
const form = useForm({
    task_description: props.promptRun.taskDescription,
});

// Watch for textarea ref and focus when it becomes available
watchEffect(() => {
    if (shouldFocusTextarea.value && taskTextareaRef.value) {
        taskTextareaRef.value.focus();
        shouldFocusTextarea.value = false;
    }
});

// Watch for button ref and focus when it becomes available
watchEffect(() => {
    if (shouldFocusButton.value && editButtonRef.value) {
        editButtonRef.value.focus();
        shouldFocusButton.value = false;
    }
});

const startEditing = () => {
    isEditing.value = true;
    shouldFocusTextarea.value = true;
};

const cancelEditing = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    shouldFocusButton.value = true;
};

const handleTranscription = (transcript: string) => {
    form.task_description = appendText(form.task_description, transcript);
};

const submit = () => {
    // Check if unregistered visitor has completed prompts
    if (!user.value && props.visitorHasCompletedPrompts) {
        showVisitorLimitModal.value = true;
        return;
    }

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

const handleRegister = () => {
    showVisitorLimitModal.value = false;
    if (openRegisterModal) {
        openRegisterModal();
    }
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
    <VisitorLimitModal
        :show="showVisitorLimitModal"
        @close="showVisitorLimitModal = false"
        @register="handleRegister"
    />

    <Card>
        <div class="flex items-start justify-between gap-4">
            <h2
                class="sr-only text-lg font-semibold text-indigo-900 sm:not-sr-only"
            >
                {{
                    isEditing
                        ? 'Edit Task & Create New Optimisation'
                        : 'Your Task'
                }}
            </h2>

            <ButtonSecondary
                v-if="!isEditing"
                ref="editButtonRef"
                type="button"
                class="inline-flex w-full items-center gap-1 sm:w-fit"
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
                ref="taskTextareaRef"
                v-model="form.task_description"
                label="Edit Task Description"
                :sr-only-label="true"
                :rows="4"
                :error="form.errors.task_description"
                required
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
