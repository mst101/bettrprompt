<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormTextarea from '@/Components/Base/Form/FormTextarea.vue';
import { router } from '@inertiajs/vue3';
import { marked } from 'marked';
import { computed, ref, watchEffect } from 'vue';
import AIProviderLinks from './AIProviderLinks.vue';

interface Props {
    optimizedPrompt: string;
    promptRunId: number;
}

const props = defineProps<Props>();

const copied = ref(false);
const isEditing = ref(false);
const showFormatted = ref(false);
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

const copyIcon = computed(() => (copied.value ? 'check' : 'clipboard-copy'));

const formattedPrompt = computed(() => {
    try {
        return marked(props.optimizedPrompt, {
            breaks: true,
            gfm: true,
        }) as string;
    } catch (err) {
        console.error('Failed to render markdown:', err);
        return props.optimizedPrompt;
    }
});

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
            <!-- Header with icon (desktop only) -->
            <div class="hidden items-center gap-2 sm:flex">
                <div class="rounded-lg bg-green-100 p-2 text-green-600">
                    <DynamicIcon name="check-circle" class="h-6 w-6" />
                </div>
                <h3 class="text-lg font-semibold text-indigo-900">
                    Your Optimised Prompt
                </h3>
            </div>

            <!-- Action Buttons (top - all screens) -->
            <div
                v-if="!isEditing"
                class="flex flex-col gap-2 sm:flex-row sm:justify-end"
            >
                <ButtonPrimary
                    ref="copyButtonRef"
                    type="button"
                    class="w-full sm:w-auto"
                    data-testid="copy-prompt-button"
                    :icon="copyIcon"
                    @click="copyToClipboard(optimizedPrompt)"
                >
                    {{ copied ? 'Copied!' : 'Copy to Clipboard' }}
                </ButtonPrimary>

                <ButtonSecondary
                    type="button"
                    class="w-full sm:w-auto"
                    data-testid="toggle-format-button"
                    :icon="showFormatted ? 'code' : 'eye'"
                    @click="showFormatted = !showFormatted"
                >
                    {{ showFormatted ? 'Show Source' : 'Show Preview' }}
                </ButtonSecondary>

                <ButtonSecondary
                    ref="editButtonRef"
                    type="button"
                    class="w-full sm:w-auto"
                    data-testid="edit-prompt-button"
                    icon="edit"
                    @click="startEditing"
                >
                    Edit Prompt
                </ButtonSecondary>
            </div>

            <!-- Edit Mode Buttons (top - all screens) -->
            <div v-else class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                <ButtonPrimary
                    type="button"
                    class="w-full sm:w-auto"
                    data-testid="save-edit-button"
                    icon="check"
                    @click="saveEdits"
                >
                    Save Changes
                </ButtonPrimary>

                <ButtonSecondary
                    type="button"
                    class="w-full sm:w-auto"
                    data-testid="cancel-edit-button"
                    @click="cancelEditing"
                >
                    Cancel
                </ButtonSecondary>
            </div>

            <AIProviderLinks v-if="!isEditing" :prompt="optimizedPrompt" />

            <!-- View Mode - Raw Markdown -->
            <div
                v-if="!isEditing && !showFormatted"
                data-testid="optimized-prompt-text"
                :class="[
                    'rounded-lg p-4 font-mono text-xs leading-relaxed text-indigo-900 transition-colors duration-300 sm:p-6 sm:text-sm',
                    copied
                        ? 'bg-indigo-200 dark:bg-indigo-300'
                        : 'bg-indigo-50 dark:bg-indigo-100',
                ]"
            >
                <pre class="wrap-break-word whitespace-pre-wrap">{{
                    optimizedPrompt
                }}</pre>
            </div>

            <!-- View Mode - Formatted Markdown -->
            <div
                v-if="!isEditing && showFormatted"
                data-testid="optimized-prompt-formatted"
                :class="[
                    'prose prose-sm prose-indigo dark:prose-invert w-full max-w-none rounded-lg p-4 transition-colors duration-300 sm:p-6',
                    copied
                        ? 'bg-indigo-200 dark:bg-indigo-300'
                        : 'bg-indigo-50 dark:bg-indigo-100',
                ]"
                v-html="formattedPrompt"
            />

            <!-- Edit Mode -->
            <FormTextarea
                v-else
                id="optimized-prompt"
                ref="textareaRef"
                v-model="editedPrompt"
                data-testid="optimized-prompt-edit"
                class="space-y-4 font-mono text-sm! sm:p-6"
                label="Optimised Prompt"
                :rows="15"
                sr-only-label
            />

            <!-- Mobile/Bottom Action Buttons -->
            <div v-if="!isEditing" class="flex flex-col gap-2 sm:hidden">
                <ButtonPrimary
                    type="button"
                    class="w-full"
                    data-testid="copy-prompt-button-mobile"
                    :icon="copyIcon"
                    @click="copyToClipboard(optimizedPrompt)"
                >
                    {{ copied ? 'Copied!' : 'Copy to Clipboard' }}
                </ButtonPrimary>

                <ButtonSecondary
                    type="button"
                    class="w-full"
                    data-testid="toggle-format-button-mobile"
                    :icon="showFormatted ? 'code' : 'eye'"
                    @click="showFormatted = !showFormatted"
                >
                    {{ showFormatted ? 'Show Source' : 'Show Preview' }}
                </ButtonSecondary>

                <ButtonSecondary
                    type="button"
                    class="w-full"
                    data-testid="edit-prompt-button-mobile"
                    icon="edit"
                    @click="startEditing"
                >
                    Edit Prompt
                </ButtonSecondary>
            </div>

            <!-- Mobile Edit Mode Buttons -->
            <div v-else class="flex flex-col gap-2 sm:hidden">
                <ButtonPrimary
                    type="button"
                    class="w-full"
                    data-testid="save-edit-button-mobile"
                    icon="check"
                    @click="saveEdits"
                >
                    Save Changes
                </ButtonPrimary>

                <ButtonSecondary
                    type="button"
                    class="w-full"
                    data-testid="cancel-edit-button-mobile"
                    @click="cancelEditing"
                >
                    Cancel
                </ButtonSecondary>
            </div>

            <AIProviderLinks v-if="!isEditing" :prompt="optimizedPrompt" />
        </div>
    </Card>
</template>
