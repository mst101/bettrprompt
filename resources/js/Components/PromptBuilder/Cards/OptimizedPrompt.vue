<script setup lang="ts">
import AIProviderLinks from '@/Components/AIProviders/AIProviderLinks.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import Card from '@/Components/Card.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    optimizedPrompt: string;
    promptRunId: number;
}

const props = defineProps<Props>();

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
    router.patch(
        route('prompt-optimizer.update-prompt', {
            promptRun: props.promptRunId,
        }),
        {
            optimized_prompt: editedPrompt.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isEditing.value = false;
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
                    <h3 class="text-lg font-semibold text-gray-900">
                        Your Optimised Prompt
                    </h3>
                </div>

                <div
                    v-if="!isEditing"
                    class="flex w-full justify-between gap-2 sm:w-auto"
                >
                    <ButtonSecondary
                        type="button"
                        data-testid="edit-prompt-button"
                        @click="startEditing"
                    >
                        <DynamicIcon name="edit" class="mr-2 h-4 w-4" />
                        Edit
                    </ButtonSecondary>
                    <ButtonPrimary
                        type="button"
                        data-testid="copy-prompt-button"
                        @click="copyToClipboard(optimizedPrompt)"
                    >
                        <DynamicIcon
                            :name="copied ? 'check' : 'clipboard-copy'"
                            class="mr-2 h-5 w-5"
                        />
                        {{ copied ? 'Copied!' : 'Copy to Clipboard' }}
                    </ButtonPrimary>
                </div>
            </div>

            <!-- AI Provider Links -->
            <div
                v-if="!isEditing"
                class="rounded-lg border border-gray-200 bg-gray-50 p-4"
            >
                <AIProviderLinks :prompt="optimizedPrompt" />
            </div>

            <!-- View Mode -->
            <div
                v-if="!isEditing"
                data-testid="optimized-prompt-text"
                class="rounded-lg bg-gray-50 py-4 font-mono text-sm leading-relaxed text-gray-800 sm:p-6"
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
