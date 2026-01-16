<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormTextarea from '@/Components/Base/Form/FormTextarea.vue';
import { computed, getCurrentInstance, nextTick, ref } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    modelValue?: number | null;
    explanation?: string | null;
    size?: 'sm' | 'md' | 'lg';
    readonly?: boolean;
    showExplanation?: boolean;
    placeholder?: string;
}

interface Emits {
    (event: 'update:modelValue', rating: number): void;
    (event: 'update:explanation', explanation: string): void;
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
});

const emit = defineEmits<Emits>();
const { t } = useI18n({ useScope: 'global' });

const hoveredStar = ref<number | null>(null);
const localExplanation = ref<string>(props.explanation || '');
const explanationRef = ref<InstanceType<typeof FormTextarea> | null>(null);

const handleStarClick = (rating: number) => {
    if (!props.readonly) {
        emit('update:modelValue', rating);
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
                textarea-class="text-sm block w-full text-indigo-900 rounded-md border-indigo-100 bg-indigo-50 dark:bg-indigo-100 inset-4 inset-shadow focus:ring-2 focus:ring-indigo-500"
                @update:model-value="handleExplanationChange"
            />
            <div
                v-if="modelValue && !readonly"
                class="mt-3 flex justify-start sm:justify-end"
            >
                <ButtonPrimary type="button" @click="handleSubmit">
                    {{
                        t('promptBuilder.components.promptRating.submitButton')
                    }}
                </ButtonPrimary>
            </div>
        </div>

        <!-- Submit button when explanation is hidden -->
        <div v-else-if="modelValue && !readonly" class="mt-1">
            <ButtonPrimary type="button" @click="handleSubmit">
                {{ t('promptBuilder.components.promptRating.submitButton') }}
            </ButtonPrimary>
        </div>
    </div>
</template>
