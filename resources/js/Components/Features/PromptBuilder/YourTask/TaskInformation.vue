<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import ButtonVoiceInput from '@/Components/Base/Button/ButtonVoiceInput.vue';
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormTextarea from '@/Components/Base/Form/FormTextarea.vue';
import VisitorLimitModal from '@/Components/Common/VisitorLimitModal.vue';
import { useTextAppend } from '@/Composables/features/useTextAppend';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import type { PromptRunResource } from '@/Types';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, inject, ref, watch, watchEffect } from 'vue';

interface Props {
    promptRun: PromptRunResource;
    visitorHasCompletedPrompts?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    visitorHasCompletedPrompts: false,
});
const { countryRoute } = useCountryRoute();

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

// Check if task description has changed
const taskDescriptionHasChanged = computed(
    () => form.task_description !== props.promptRun.taskDescription,
);

const submit = () => {
    // Check if unregistered visitor has completed prompts
    if (!user.value && props.visitorHasCompletedPrompts) {
        showVisitorLimitModal.value = true;
        return;
    }

    form.post(
        countryRoute('prompt-builder.create-child-from-task', {
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
                        ? $t(
                              'promptBuilder.components.taskInformation.editTitle',
                          )
                        : $t('promptBuilder.components.taskInformation.title')
                }}
            </h2>

            <ButtonSecondary
                v-if="!isEditing"
                ref="editButtonRef"
                type="button"
                class="inline-flex w-full items-center gap-1 sm:w-fit"
                @click="startEditing"
            >
                <DynamicIcon name="edit" class="mr-2 -ml-1 h-4 w-4" />
                {{ $t('promptBuilder.components.taskInformation.editAction') }}
            </ButtonSecondary>

            <ButtonSecondary
                v-else
                type="button"
                :disabled="form.processing"
                @click="cancelEditing"
            >
                {{ $t('common.buttons.cancel') }}
            </ButtonSecondary>
        </div>

        <!-- View Mode -->
        <FormTextarea
            v-if="!isEditing"
            id="task-description"
            :model-value="promptRun.taskDescription"
            :label="
                $t('promptBuilder.components.taskInformation.descriptionLabel')
            "
            :sr-only-label="true"
            :disabled="true"
            required
        />

        <!-- Edit Mode -->
        <form v-else @submit.prevent="submit">
            <FormTextarea
                id="task-description-edit"
                ref="taskTextareaRef"
                v-model="form.task_description"
                :label="
                    $t(
                        'promptBuilder.components.taskInformation.descriptionEditLabel',
                    )
                "
                :sr-only-label="true"
                :error="form.errors.task_description"
                required
                :placeholder="
                    $t(
                        'promptBuilder.components.taskInformation.descriptionPlaceholder',
                    )
                "
            />

            <div class="mt-4 flex items-center justify-between">
                <ButtonVoiceInput @transcription="handleTranscription" />

                <ButtonPrimary
                    type="submit"
                    :disabled="
                        !form.task_description ||
                        form.task_description.trim() === '' ||
                        form.processing ||
                        !taskDescriptionHasChanged
                    "
                    :loading="form.processing"
                >
                    {{
                        $t('promptBuilder.components.taskInformation.optimise')
                    }}
                    <DynamicIcon name="arrow-right" class="ml-2 h-4 w-4" />
                </ButtonPrimary>
            </div>
        </form>
    </Card>
</template>
