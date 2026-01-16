<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormTextarea from '@/Components/Base/Form/FormTextarea.vue';
import { computed, getCurrentInstance, nextTick, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    modelValue?: number | null;
    explanation?: string | null;
    size?: 'sm' | 'md' | 'lg';
    readonly?: boolean;
    showExplanation?: boolean;
    placeholder?: string;
    isSaved?: boolean;
}

interface Emits {
    (event: 'update:modelValue', rating: number): void;
    (event: 'update:explanation', explanation: string): void;
    (event: 'rateImmediately', rating: number): void;
    (
        event: 'submit',
        data: { rating: number; explanation: string | null },
    ): void;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    explanation: null,
    size: 'md',
    readonly: false,
    showExplanation: true,
    placeholder: '',
    isSaved: false,
});

const emit = defineEmits<Emits>();
const { t } = useI18n({ useScope: 'global' });

const hoveredStar = ref<number | null>(null);
const localExplanation = ref<string>(props.explanation || '');
const explanationRef = ref<InstanceType<typeof FormTextarea> | null>(null);
const isExplanationFocused = ref<boolean>(false);

// Sync localExplanation when explanation prop changes (e.g., switching between questions)
watch(
    () => props.explanation,
    (newExplanation) => {
        localExplanation.value = newExplanation || '';
    },
);

const handleStarClick = (rating: number) => {
    if (!props.readonly) {
        emit('update:modelValue', rating);
        // Immediately save the rating
        emit('rateImmediately', rating);
        if (props.showExplanation) {
            nextTick(() => {
                explanationRef.value?.focus();
            });
        }
    }
};

const handleStarHover = (rating: number) => {
    if (!props.readonly) {
        hoveredStar.value = rating;
    }
};

const handleMouseLeave = () => {
    hoveredStar.value = null;
};

const handleExplanationChange = (value: string) => {
    localExplanation.value = value;
    emit('update:explanation', value);
};

const handleSubmit = () => {
    if (props.modelValue) {
        emit('submit', {
            rating: props.modelValue,
            explanation: localExplanation.value || null,
        });
    }
};

const displayRating = computed(
    () => hoveredStar.value ?? props.modelValue ?? 0,
);

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'size-5';
        case 'md':
            return 'size-6';
        case 'lg':
            return 'size-8';
        default:
            return 'size-6';
    }
});

const explanationPlaceholder = computed(() => {
    return (
        props.placeholder ||
        t('promptBuilder.components.promptRating.placeholder')
    );
});

const textareaClass = computed(() => {
    const baseClasses =
        'text-sm block w-full rounded-md border-indigo-100 bg-indigo-50 dark:bg-indigo-100 inset-4 inset-shadow focus:ring-2 focus:ring-indigo-500';
    // Text colour based on state:
    // - Unsaved: text-indigo-900
    // - Saved & not focused: text-indigo-600 (reduced contrast)
    // - Saved & focused: text-indigo-800 (darker when editing)
    let textColorClass = 'text-indigo-900';
    if (props.isSaved && !props.readonly) {
        textColorClass = isExplanationFocused.value
            ? 'text-indigo-950'
            : 'text-indigo-600';
    }
    return `${baseClasses} ${textColorClass}`;
});

const instanceId = getCurrentInstance()?.uid ?? 0;
const explanationId = `prompt-rating-explanation-${instanceId}`;
</script>

<template>
    <div class="flex w-full flex-col items-center gap-4">
        <!-- Star rating -->
        <div
            class="flex items-center gap-2"
            role="radiogroup"
            :aria-label="t('promptBuilder.components.promptRating.ariaLabel')"
            @mouseleave="handleMouseLeave"
        >
            <button
                v-for="star in 5"
                :key="star"
                type="button"
                :disabled="readonly"
                :aria-label="
                    t('promptBuilder.components.promptRating.starLabel', {
                        star,
                    })
                "
                :aria-checked="modelValue === star"
                role="radio"
                class="rounded p-1 transition-transform duration-150 outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-indigo-500"
                :class="{
                    'cursor-pointer hover:scale-110': !readonly,
                    'cursor-default': readonly,
                    'text-yellow-400': star <= displayRating,
                    'text-indigo-300': star > displayRating,
                }"
                @click="handleStarClick(star)"
                @mouseenter="handleStarHover(star)"
            >
                <DynamicIcon
                    name="star"
                    :class="[
                        sizeClasses,
                        star <= displayRating ? 'fill-current' : '',
                    ]"
                />
            </button>
        </div>

        <!-- Optional explanation textarea + submit -->
        <div
            v-if="showExplanation && modelValue"
            class="justify-center-center flex w-full flex-col sm:max-w-xl"
        >
            <FormTextarea
                :id="explanationId"
                ref="explanationRef"
                :label="
                    t('promptBuilder.components.promptRating.explanationLabel')
                "
                :sr-only-label="true"
                :model-value="localExplanation"
                :placeholder="explanationPlaceholder"
                :disabled="readonly"
                :rows="2"
                :class="{ 'opacity-75': isSaved && !readonly }"
                :textarea-class="textareaClass"
                @update:model-value="handleExplanationChange"
                @focus="isExplanationFocused = true"
                @blur="isExplanationFocused = false"
            />
            <div
                v-if="modelValue && !readonly"
                class="mt-3 flex justify-start sm:justify-end"
            >
                <ButtonSecondary
                    v-if="isSaved && localExplanation"
                    size="sm"
                    type="button"
                    @click="handleSubmit"
                >
                    {{
                        t(
                            'promptBuilder.components.promptRating.updateExplanation',
                        )
                    }}
                </ButtonSecondary>
                <ButtonPrimary
                    v-else
                    size="sm"
                    type="button"
                    @click="handleSubmit"
                >
                    {{
                        t(
                            'promptBuilder.components.promptRating.addExplanation',
                        )
                    }}
                </ButtonPrimary>
            </div>
        </div>
    </div>
</template>
