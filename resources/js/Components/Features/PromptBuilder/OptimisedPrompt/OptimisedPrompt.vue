<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormTextarea from '@/Components/Base/Form/FormTextarea.vue';
import PromptRating from '@/Components/Features/PromptBuilder/PromptRating.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import { analyticsService } from '@/services/analytics';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { marked } from 'marked';
import { computed, ref, watchEffect } from 'vue';
import AIProviderLinks from './AIProviderLinks.vue';

interface Props {
    optimizedPrompt: string;
    promptRunId: number;
}

const props = defineProps<Props>();
const { countryRoute } = useCountryRoute();

const copied = ref(false);
const isEditing = ref(false);
const showFormatted = ref(true);
const showProvidersDropdown = ref(false);
const editedPrompt = ref(props.optimizedPrompt);
const shouldFocusTextarea = ref(false);
const shouldFocusEditButton = ref(false);
const shouldFocusCopyButton = ref(false);
const textareaRef = ref<InstanceType<typeof FormTextarea> | null>(null);
const editButtonRef = ref<InstanceType<typeof ButtonSecondary> | null>(null);
const copyButtonRef = ref<InstanceType<typeof ButtonPrimary> | null>(null);

// Rating state
const userRating = ref<number | null>(null);
const ratingExplanation = ref<string | null>(null);
const hasRated = ref<boolean>(false);
const isSavingRating = ref<boolean>(false);

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

        analyticsService.track({
            name: 'prompt_copied',
            properties: {
                prompt_run_id: props.promptRunId,
                prompt_length: text.length,
            },
        });

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
        countryRoute('prompt-builder.update-prompt', {
            promptRun: props.promptRunId,
        }),
        {
            optimized_prompt: editedPrompt.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                // Track prompt edit
                const editPercentage = Math.abs(
                    ((editedPrompt.value.length -
                        props.optimizedPrompt.length) /
                        props.optimizedPrompt.length) *
                        100,
                );

                analyticsService.track({
                    name: 'prompt_edited',
                    properties: {
                        prompt_run_id: props.promptRunId,
                        original_length: props.optimizedPrompt.length,
                        edited_length: editedPrompt.value.length,
                        edit_percentage: parseFloat(editPercentage.toFixed(2)),
                    },
                });

                isEditing.value = false;
                shouldFocusCopyButton.value = true;
            },
        },
    );
};

const handleRatingSubmit = async (data: {
    rating: number;
    explanation: string | null;
}) => {
    userRating.value = data.rating;
    ratingExplanation.value = data.explanation;
    hasRated.value = true;
    isSavingRating.value = true;

    try {
        // Save to database immediately (not just analytics event)
        await axios.post(
            countryRoute('api.prompt-runs.rate', {
                promptRun: props.promptRunId,
            }),
            {
                rating: data.rating,
                explanation: data.explanation,
            },
        );

        // Also fire analytics event (for consent-aware tracking)
        analyticsService.track({
            name: 'prompt_rated',
            properties: {
                prompt_run_id: props.promptRunId,
                rating: data.rating,
                has_explanation: !!data.explanation,
                explanation_length: data.explanation?.length ?? 0,
                prompt_length: props.optimizedPrompt.length,
            },
        });
    } catch (error) {
        console.error('Failed to save rating:', error);
        hasRated.value = false;
    } finally {
        isSavingRating.value = false;
    }
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
                <h2 class="text-lg font-semibold text-indigo-900">
                    {{ $t('promptBuilder.components.optimizedPrompt.title') }}
                </h2>
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
                    {{
                        copied
                            ? $t('common.buttons.copied')
                            : $t(
                                  'promptBuilder.components.optimizedPrompt.copy',
                              )
                    }}
                </ButtonPrimary>

                <ButtonSecondary
                    type="button"
                    class="w-full sm:w-auto"
                    data-testid="toggle-format-button"
                    :icon="showFormatted ? 'code' : 'eye'"
                    @click="showFormatted = !showFormatted"
                >
                    {{
                        showFormatted
                            ? $t(
                                  'promptBuilder.components.optimizedPrompt.showSource',
                              )
                            : $t(
                                  'promptBuilder.components.optimizedPrompt.showPreview',
                              )
                    }}
                </ButtonSecondary>

                <ButtonSecondary
                    ref="editButtonRef"
                    type="button"
                    class="w-full sm:w-auto"
                    data-testid="edit-prompt-button"
                    icon="edit"
                    @click="startEditing"
                >
                    {{ $t('promptBuilder.components.optimizedPrompt.edit') }}
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
                    {{
                        $t(
                            'promptBuilder.components.optimizedPrompt.saveChanges',
                        )
                    }}
                </ButtonPrimary>

                <ButtonSecondary
                    type="button"
                    class="w-full sm:w-auto"
                    data-testid="cancel-edit-button"
                    @click="cancelEditing"
                >
                    {{ $t('common.buttons.cancel') }}
                </ButtonSecondary>
            </div>

            <!-- Desktop: Show providers inline -->
            <div v-if="!isEditing" class="hidden sm:block">
                <AIProviderLinks
                    :prompt="optimizedPrompt"
                    :heading-number="3"
                />
            </div>

            <!-- Mobile: Show providers dropdown -->
            <div v-if="!isEditing" class="sm:hidden">
                <button
                    type="button"
                    class="mb-4 flex w-full items-center justify-between rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-medium text-indigo-900 hover:bg-indigo-100"
                    @click="showProvidersDropdown = !showProvidersDropdown"
                >
                    <span>
                        {{
                            $t(
                                'promptBuilder.components.optimizedPrompt.useProvider',
                            )
                        }}
                    </span>
                    <DynamicIcon
                        :name="
                            showProvidersDropdown
                                ? 'chevron-up'
                                : 'chevron-down'
                        "
                        class="h-4 w-4"
                    />
                </button>
                <div v-if="showProvidersDropdown" class="mb-4">
                    <AIProviderLinks
                        :prompt="optimizedPrompt"
                        :heading-number="3"
                    />
                </div>
            </div>

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
            <!-- eslint-disable-next-line vue/no-v-html -->
            <div
                v-if="!isEditing && showFormatted"
                data-testid="optimized-prompt-formatted"
                :class="[
                    'prose prose-sm prose-indigo prose-h1:text-2xl prose-h2:text-xl prose-h3:text-lg dark:prose-invert w-full max-w-none rounded-lg p-4 transition-colors duration-300 sm:p-6',
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
                :label="$t('promptBuilder.components.optimizedPrompt.label')"
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
                    {{
                        copied
                            ? $t('common.buttons.copied')
                            : $t(
                                  'promptBuilder.components.optimizedPrompt.copy',
                              )
                    }}
                </ButtonPrimary>

                <ButtonSecondary
                    type="button"
                    class="w-full"
                    data-testid="toggle-format-button-mobile"
                    :icon="showFormatted ? 'code' : 'eye'"
                    @click="showFormatted = !showFormatted"
                >
                    {{
                        showFormatted
                            ? $t(
                                  'promptBuilder.components.optimizedPrompt.showSource',
                              )
                            : $t(
                                  'promptBuilder.components.optimizedPrompt.showPreview',
                              )
                    }}
                </ButtonSecondary>

                <ButtonSecondary
                    type="button"
                    class="w-full"
                    data-testid="edit-prompt-button-mobile"
                    icon="edit"
                    @click="startEditing"
                >
                    {{ $t('promptBuilder.components.optimizedPrompt.edit') }}
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
                    {{
                        $t(
                            'promptBuilder.components.optimizedPrompt.saveChanges',
                        )
                    }}
                </ButtonPrimary>

                <ButtonSecondary
                    type="button"
                    class="w-full"
                    data-testid="cancel-edit-button-mobile"
                    @click="cancelEditing"
                >
                    {{ $t('common.buttons.cancel') }}
                </ButtonSecondary>
            </div>

            <!-- AI Provider Links (visible on all screens) -->
            <AIProviderLinks
                v-if="!isEditing"
                :prompt="optimizedPrompt"
                :heading-number="4"
            />

            <!-- Rating Section -->
            <div v-if="!isEditing" class="mt-6 border-t border-indigo-200 pt-6">
                <div class="flex flex-col items-center gap-3">
                    <h5 class="text-sm font-medium text-indigo-700">
                        {{
                            $t(
                                'promptBuilder.components.optimizedPrompt.ratePrompt',
                            )
                        }}
                    </h5>
                    <PromptRating
                        v-model="userRating"
                        :explanation="ratingExplanation"
                        size="md"
                        :show-explanation="true"
                        @submit="handleRatingSubmit"
                    />
                    <p
                        v-if="hasRated && !isSavingRating"
                        class="text-sm text-green-600"
                    >
                        {{
                            $t(
                                'promptBuilder.components.optimizedPrompt.ratingThankYou',
                            )
                        }}
                    </p>
                    <p v-if="isSavingRating" class="text-sm text-indigo-500">
                        {{ $t('common.labels.saving') }}
                    </p>
                </div>
            </div>
        </div>
    </Card>
</template>
