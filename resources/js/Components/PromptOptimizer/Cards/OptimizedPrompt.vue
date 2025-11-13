<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import Card from '@/Components/Card.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import { ref } from 'vue';

interface Props {
    optimizedPrompt: string;
    promptRunId: number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'save', editedPrompt: string): void;
}>();

const copied = ref(false);
const isEditing = ref(false);
const editedPrompt = ref(props.optimizedPrompt);

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
};

const cancelEditing = () => {
    editedPrompt.value = props.optimizedPrompt;
    isEditing.value = false;
};

const saveEdits = () => {
    emit('save', editedPrompt.value);
    isEditing.value = false;
};
</script>

<template>
    <Card data-testid="optimized-prompt-display">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="rounded-lg bg-green-100 p-2 text-green-600">
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        Your Optimised Prompt
                    </h3>
                </div>

                <div v-if="!isEditing" class="flex gap-2">
                    <ButtonSecondary
                        type="button"
                        data-testid="edit-prompt-button"
                        @click="startEditing"
                    >
                        <svg
                            class="mr-2 h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            />
                        </svg>
                        Edit
                    </ButtonSecondary>
                    <ButtonPrimary
                        type="button"
                        data-testid="copy-prompt-button"
                        @click="copyToClipboard(optimizedPrompt)"
                    >
                        <svg
                            v-if="!copied"
                            class="mr-2 h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"
                            />
                        </svg>
                        <svg
                            v-else
                            class="mr-2 h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        {{ copied ? 'Copied!' : 'Copy to Clipboard' }}
                    </ButtonPrimary>
                </div>
            </div>

            <!-- View Mode -->
            <div
                v-if="!isEditing"
                data-testid="optimized-prompt-text"
                class="rounded-lg bg-gray-50 p-6 font-mono text-sm leading-relaxed text-gray-800"
            >
                <pre class="wrap-break-word whitespace-pre-wrap">{{
                    optimizedPrompt
                }}</pre>
            </div>

            <!-- Edit Mode -->
            <div v-else class="space-y-4">
                <FormTextarea
                    id="optimized_prompt"
                    v-model="editedPrompt"
                    label=""
                    :rows="15"
                    class="font-mono text-sm"
                />

                <div class="flex items-center justify-end gap-3">
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
