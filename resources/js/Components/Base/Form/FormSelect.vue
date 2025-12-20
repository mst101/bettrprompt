<script setup lang="ts">
import type { FormSelectProps, Nullable } from '@/Types';
import { onMounted, ref } from 'vue';

const props = withDefaults(defineProps<FormSelectProps>(), {
    modelValue: '',
    error: '',
    required: false,
    disabled: false,
    labelSrOnly: false,
    placeholder: 'Please select...',
    showPlaceholder: true,
    helpText: '',
    autofocus: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
}>();

defineOptions({
    inheritAttrs: false,
});

const select = ref<Nullable<HTMLSelectElement>>(null);

onMounted(() => {
    if (props.autofocus && select.value) {
        select.value.focus();
    }
});
</script>

<template>
    <div>
        <label
            :for="props.id"
            class="block text-sm font-medium text-indigo-900"
            :class="props.labelSrOnly ? 'sr-only' : ''"
        >
            {{ props.label }}
            <span v-if="props.required" class="text-red-500">*</span>
        </label>

        <select
            :id="props.id"
            ref="select"
            :value="props.modelValue"
            :required="props.required"
            :disabled="props.disabled"
            v-bind="$attrs"
            class="mt-1 block w-full rounded-md border-indigo-300 bg-indigo-50 text-indigo-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            :class="{ 'cursor-not-allowed bg-indigo-100': props.disabled }"
            @change="
                emit(
                    'update:modelValue',
                    ($event.target as HTMLSelectElement).value,
                )
            "
        >
            <option v-if="props.showPlaceholder" value="" disabled>
                {{ props.placeholder }}
            </option>
            <option
                v-for="option in props.options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>

        <p v-if="props.helpText" class="mt-1 text-xs text-indigo-600">
            {{ props.helpText }}
        </p>

        <p v-if="props.error" class="mt-1 text-sm text-red-600">
            {{ props.error }}
        </p>
    </div>
</template>
