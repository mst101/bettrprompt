<script setup lang="ts">
import FormTextarea from '@/Components/FormTextarea.vue';
import { computed, nextTick, ref } from 'vue';

interface Option {
    value: string;
    label: string;
    description: string;
}

interface Props {
    modelValue: string[];
    options: Option[];
    otherValue?: string;
    disabled?: boolean;
    error?: string;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    otherValue: '',
    error: '',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string[]): void;
    (e: 'update:otherValue', value: string): void;
}>();

const otherTextarea = ref<InstanceType<typeof FormTextarea> | null>(null);

const isChecked = (value: string) => {
    return props.modelValue.includes(value);
};

const toggleOption = async (value: string) => {
    if (props.disabled) return;

    const currentValues = [...props.modelValue];
    const index = currentValues.indexOf(value);

    if (index > -1) {
        currentValues.splice(index, 1);
        // If unchecking "other", clear the other text
        if (value === 'other') {
            emit('update:otherValue', '');
        }
    } else {
        currentValues.push(value);
    }

    emit('update:modelValue', currentValues);

    // If checking "other", focus the textarea after the transition
    if (value === 'other' && index === -1) {
        await nextTick();
        // Wait for transition to complete
        setTimeout(() => {
            // Find the textarea element within the FormTextarea component
            const textarea =
                otherTextarea.value?.$el?.querySelector('textarea');
            textarea?.focus();
        }, 250);
    }
};

const showOtherInput = computed(() => {
    return props.modelValue.includes('other');
});
</script>

<template>
    <div>
        <div class="space-y-3">
            <label
                v-for="option in options"
                :key="option.value"
                class="flex items-start gap-3 rounded-lg border border-indigo-200 p-4 transition-colors hover:bg-indigo-50 dark:hover:bg-indigo-100"
                :class="{
                    'cursor-pointer': !disabled,
                    'cursor-not-allowed opacity-50': disabled,
                    'border-indigo-500 bg-indigo-50 dark:bg-indigo-100':
                        isChecked(option.value),
                    'hover:bg-indigo-50': isChecked(option.value),
                }"
            >
                <input
                    type="checkbox"
                    :value="option.value"
                    :checked="isChecked(option.value)"
                    :disabled="disabled"
                    class="mt-1 h-4 w-4 rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                    @change="toggleOption(option.value)"
                />
                <div class="flex-1">
                    <div class="text-sm font-medium text-indigo-900">
                        {{ option.label }}
                    </div>
                    <div class="mt-1 text-sm text-indigo-700">
                        {{ option.description }}
                    </div>
                </div>
            </label>
        </div>

        <!-- Other Text Input -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="showOtherInput" class="mt-4">
                <FormTextarea
                    id="other-text"
                    ref="otherTextarea"
                    :model-value="otherValue"
                    label="Please describe the feature you'd like"
                    :disabled="disabled"
                    placeholder="Describe the feature you'd like to see..."
                    :rows="3"
                    :maxlength="500"
                    @update:model-value="emit('update:otherValue', $event)"
                />
                <div class="mt-1 text-right text-xs text-indigo-500">
                    {{ otherValue.length }} / 500 characters
                </div>
            </div>
        </Transition>

        <!-- Error Message -->
        <p v-if="error" class="mt-2 text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>
