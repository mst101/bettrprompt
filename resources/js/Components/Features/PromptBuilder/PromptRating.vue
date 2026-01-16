<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import { computed, ref } from 'vue';

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
    placeholder: 'Optionally, tell us more about your rating...',
});

const emit = defineEmits<Emits>();

const hoveredStar = ref<number | null>(null);
const localExplanation = ref<string>(props.explanation || '');

const handleStarClick = (rating: number) => {
    if (!props.readonly) {
        emit('update:modelValue', rating);
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

const handleExplanationChange = (event: Event) => {
    const value = (event.target as HTMLTextAreaElement).value;
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
</script>

<template>
    <div class="flex flex-col gap-3">
        <!-- Star rating -->
        <div
            class="flex items-center gap-1"
            role="radiogroup"
            aria-label="Rate from 1 to 5 stars"
            @mouseleave="handleMouseLeave"
        >
            <button
                v-for="star in 5"
                :key="star"
                type="button"
                :disabled="readonly"
                :aria-label="`Rate ${star} stars`"
                :aria-checked="modelValue === star"
                role="radio"
                class="transition-transform duration-150"
                :class="{
                    'cursor-pointer hover:scale-110': !readonly,
                    'cursor-default': readonly,
                    'text-yellow-400': star <= displayRating,
                    'text-gray-300': star > displayRating,
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

        <!-- Optional explanation textarea -->
        <div v-if="showExplanation && modelValue" class="w-full">
            <textarea
                :value="localExplanation"
                :placeholder="placeholder"
                :readonly="readonly"
                rows="3"
                class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-indigo-500"
                :class="{ 'bg-gray-50': readonly }"
                @input="handleExplanationChange"
            />
        </div>

        <!-- Submit button (optional, could be handled by parent) -->
        <button
            v-if="modelValue && !readonly"
            type="button"
            class="mt-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
            @click="handleSubmit"
        >
            Submit Rating
        </button>
    </div>
</template>
