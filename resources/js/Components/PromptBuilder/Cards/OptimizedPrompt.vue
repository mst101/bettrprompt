<script setup lang="ts">
import AIProviderLinks from '@/Components/AIProviders/AIProviderLinks.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import Card from '@/Components/Card.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import { router } from '@inertiajs/vue3';
import { ref, watchEffect } from 'vue';

interface Props {
    optimizedPrompt: string;
    promptRunId: number;
}

const props = defineProps<Props>();

const copied = ref(false);
const isEditing = ref(false);
const editedPrompt = ref(props.optimizedPrompt);
const shouldFocusTextarea = ref(false);
const shouldFocusEditButton = ref(false);
const shouldFocusCopyButton = ref(false);
const textareaRef = ref<InstanceType<typeof FormTextarea> | null>(null);
const editButtonRef = ref<InstanceType<typeof ButtonSecondary> | null>(null);
const copyButtonRef = ref<InstanceType<typeof ButtonPrimary> | null>(null);

// Watch for textarea ref and focus when it becomes available
watchEffect(() => {
    if (shouldFocusTextarea.value && textareaRef.value) {
        textareaRef.value.focus({ cursorPosition: 'start' });
        shouldFocusTextarea.value = false;
    }
});

// Watch for edit button ref and focus when it becomes available
watchEffect(() => {
    if (shouldFocusEditButton.value && editButtonRef.value) {
        editButtonRef.value.focus();
        shouldFocusEditButton.value = false;
    }
});

// Watch for copy button ref and focus when it becomes available
watchEffect(() => {
    if (shouldFocusCopyButton.value && copyButtonRef.value) {
        copyButtonRef.value.focus();
        shouldFocusCopyButton.value = false;
    }
});

const copyToClipboard = async (text: string) => {
    try {
        await navigator.clipboard.writeText(text);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

const startEditing = () => {
    editedPrompt.value = props.optimizedPrompt;
    isEditing.value = true;
    shouldFocusTextarea.value = true;
};

const cancelEditing = () => {
    editedPrompt.value = props.optimizedPrompt;
    isEditing.value = false;
    shouldFocusEditButton.value = true;
};

const saveEdits = () => {
    router.patch(
        route('prompt-builder.update-prompt', {
            promptRun: props.promptRunId,
        }),
        {
            optimized_prompt: editedPrompt.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isEditing.value = false;
                shouldFocusCopyButton.value = true;
            },
        },
    );
};
</script>

<template>
    <Card data-testid="optimized-prompt-display">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="hidden items-center gap-2 sm:flex">
                    <div class="rounded-lg bg-green-100 p-2 text-green-600">
                        <DynamicIcon name="check-circle" class="h-6 w-6" />
                    </div>
                    <h3 class="text-lg font-semibold text-indigo-900">
                        Your Optimised Prompt
                    </h3>
                </div>

                <div
                    v-if="!isEditing"
                    class="flex w-full justify-between gap-2 sm:w-auto"
                >
                    <ButtonSecondary
                        ref="editButtonRef"
                        type="button"
                        data-testid="edit-prompt-button"
                        @click="startEditing"
                    >
                        <DynamicIcon name="edit" class="mr-2 h-4 w-4" />
                        Edit
                    </ButtonSecondary>

                    <ButtonPrimary
                        ref="copyButtonRef"
                        type="button"
                        data-testid="copy-prompt-button"
                        @click="copyToClipboard(optimizedPrompt)"
                    >
                        <DynamicIcon
                            :name="copied ? 'check' : 'clipboard-copy'"
                            class="h-5 w-5"
                        />
                        {{ copied ? 'Copied!' : 'Copy to Clipboard' }}
                    </ButtonPrimary>
                </div>

                <div v-else class="flex items-center justify-end gap-3">
                    <ButtonSecondary
                        type="button"
                        data-testid="cancel-edit-button"
                        @click="cancelEditing"
                    >
                        Cancel
                    </ButtonSecondary>
                    <ButtonPrimary
                        type="button"
                        data-testid="save-edit-button"
                        @click="saveEdits"
                    >
                        Save Changes
                    </ButtonPrimary>
                </div>
            </div>

            <AIProviderLinks
                v-if="!isEditing"
                :prompt="optimizedPrompt"
                class="rounded-lg bg-indigo-50 p-4 dark:bg-indigo-100"
            />

            <!-- View Mode -->
            <div
                v-if="!isEditing"
                data-testid="optimized-prompt-text"
                :class="[
                    'rounded-lg py-4 font-mono text-sm leading-relaxed text-indigo-900 transition-colors duration-300 sm:p-6',
                    copied
                        ? 'bg-indigo-200 dark:bg-indigo-300'
                        : 'bg-indigo-50 dark:bg-indigo-100',
                ]"
            >
                <pre class="wrap-break-word whitespace-pre-wrap">{{
                    optimizedPrompt
                }}</pre>
            </div>

            <!-- Edit Mode -->
            <FormTextarea
                v-else
                id="optimized-prompt"
                ref="textareaRef"
                v-model="editedPrompt"
                data-testid="optimized-prompt-edit"
                class="space-y-4 font-mono text-sm sm:p-6"
                label="Optimised Prompt"
                :rows="15"
                sr-only-label
            />

            <div class="rounded-lg bg-blue-50 p-4">
                <p class="text-sm text-blue-900">
                    <strong>💡 Tip:</strong> Copy this prompt and use it with
                    your preferred AI assistant (ChatGPT, Claude, etc.) for
                    better, more personalised responses!
                </p>
            </div>
        </div>
    </Card>
</template>
