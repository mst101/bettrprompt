<script setup lang="ts">
import type { FormTextareaProps } from '@/Types';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

const props = withDefaults(defineProps<FormTextareaProps>(), {
    modelValue: '',
    rows: 3,
    error: '',
    required: false,
    placeholder: '',
    disabled: false,
    helpText: '',
    autofocus: false,
    isSubmitting: false,
    textareaClass: '',
    srOnlyLabel: false,
});
const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();
// Configuration constants
const MIN_ROWS = props.rows;
const MAX_ROWS = 16;
const DEFAULT_LINE_HEIGHT = 20;

defineOptions({
    inheritAttrs: false,
});

const textareaClasses = computed(() => {
    if (props.textareaClass) {
        return props.textareaClass;
    }
    return [
        'mt-2 sm:text-lg block w-full text-indigo-900 rounded-md border-indigo-100 bg-indigo-50 dark:bg-indigo-100 inset-4 inset-shadow focus:ring-2 focus:ring-indigo-500',
        {
            'cursor-not-allowed': props.disabled || props.isSubmitting,
        },
        {
            'border-red-300 focus:border-red-500 focus:ring-red-500':
                !!props.error,
        },
    ];
});

const textarea = ref<HTMLTextAreaElement | null>(null);
const dynamicRows = ref(props.rows);

// Extract style metrics from textarea element
const getStyleMetrics = (element: HTMLTextAreaElement) => {
    const styles = window.getComputedStyle(element);
    const paddingTop = parseInt(styles.paddingTop, 10) || 0;
    const paddingBottom = parseInt(styles.paddingBottom, 10) || 0;
    const borderTopWidth = parseInt(styles.borderTopWidth, 10) || 0;
    const borderBottomWidth = parseInt(styles.borderBottomWidth, 10) || 0;

    const totalPadding =
        paddingTop + paddingBottom + borderTopWidth + borderBottomWidth;
    const lineHeight = parseInt(styles.lineHeight, 10) || DEFAULT_LINE_HEIGHT;

    return { totalPadding, lineHeight };
};

// Calculate exact number of rows needed for content (used on initial load)
const calculateExactRows = async () => {
    if (!textarea.value) return;

    // Measure content height at rows=1 to avoid height inflation from reserved space
    dynamicRows.value = 1;
    await nextTick();

    const textareaElement = textarea.value;
    const scrollHeight = textareaElement.scrollHeight;
    const { totalPadding, lineHeight } = getStyleMetrics(textareaElement);

    const contentHeight = Math.max(0, scrollHeight - totalPadding);
    const rowsNeeded = Math.ceil(contentHeight / lineHeight);

    // Clamp between MIN_ROWS and MAX_ROWS
    dynamicRows.value = Math.max(MIN_ROWS, Math.min(rowsNeeded, MAX_ROWS));
};

// Incremental expansion/shrinking (used while typing)
const updateDynamicRows = async () => {
    if (!textarea.value) return;

    // Capture current display size before we change it for measurement
    const previousDisplay = dynamicRows.value;

    // Measure content height at rows=1 to avoid height inflation from reserved space
    dynamicRows.value = 1;
    await nextTick();

    const textareaElement = textarea.value;
    const scrollHeight = textareaElement.scrollHeight;
    const { totalPadding, lineHeight } = getStyleMetrics(textareaElement);

    const contentHeight = Math.max(0, scrollHeight - totalPadding);
    const rowsOfContent = contentHeight / lineHeight;

    // Expand when content fills the second-to-last row (~100%)
    // Example: at 3 rows displayed, expand when content needs >2.0 rows (after row 2 fills)
    // Use 0.9 to expand slightly after the second-to-last row fills for smooth UX
    const expandThreshold = previousDisplay - 0.9;
    const shrinkThreshold = previousDisplay - 1.9; // Shrink with 1 row of hysteresis

    let rowsToDisplay = previousDisplay;
    if (rowsOfContent >= expandThreshold) {
        // Content is filling the second-to-last row, expand to next row
        rowsToDisplay = Math.min(previousDisplay + 1, MAX_ROWS);
    } else if (previousDisplay > MIN_ROWS && rowsOfContent <= shrinkThreshold) {
        // Content has shrunk enough to no longer need current display size, shrink by 1
        rowsToDisplay = previousDisplay - 1;
    }

    // Clamp between MIN_ROWS and MAX_ROWS
    dynamicRows.value = Math.max(MIN_ROWS, Math.min(rowsToDisplay, MAX_ROWS));
};

watch(() => props.modelValue, updateDynamicRows);

// Calculate initial rows needed on mount (calculate exact rows, not incremental)
onMounted(() => {
    calculateExactRows();
});

const focus = (options?: { cursorPosition?: 'start' | 'end' }) => {
    textarea.value?.focus();

    if (options?.cursorPosition === 'start' && textarea.value) {
        textarea.value.setSelectionRange(0, 0);
        textarea.value.scrollTop = 0;
    } else if (options?.cursorPosition === 'end' && textarea.value) {
        const length = textarea.value.value.length;
        textarea.value.setSelectionRange(length, length);
    }
};

defineExpose({ focus });

const labelClass = computed(() =>
    props.srOnlyLabel ? 'sr-only' : 'block text-sm font-medium text-indigo-900',
);
</script>

<template>
    <div>
        <label v-if="props.label" :for="props.id" :class="labelClass">
            {{ props.label }}
            <span v-if="props.required" class="text-red-500">*</span>
        </label>

        <textarea
            :id="props.id"
            ref="textarea"
            data-testid="textarea-task-description"
            :value="props.modelValue"
            :rows="dynamicRows"
            :placeholder="props.placeholder"
            :required="props.required"
            :disabled="props.disabled"
            :maxlength="props.maxlength"
            :autofocus="props.autofocus"
            :aria-label="props.srOnlyLabel ? props.label : undefined"
            v-bind="$attrs"
            class="resize-none placeholder-indigo-700"
            :class="textareaClasses"
            @input="
                emit(
                    'update:modelValue',
                    ($event.target as HTMLTextAreaElement).value,
                )
            "
        />

        <p v-if="props.helpText" class="mt-1 text-xs text-indigo-600">
            {{ props.helpText }}
        </p>

        <p v-if="props.error" class="mt-1 text-sm text-red-600">
            {{ props.error }}
        </p>
    </div>
</template>
