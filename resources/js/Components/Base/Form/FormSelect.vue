<script setup lang="ts">
import type { FormSelectProps, Nullable } from '@/Types';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = withDefaults(defineProps<Partial<FormSelectProps>>(), {
    modelValue: '',
    error: '',
    required: false,
    disabled: false,
    labelSrOnly: false,
    placeholder: '',
    showPlaceholder: true,
    helpText: '',
    autofocus: false,
    options: () => [],
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
}>();

defineOptions({
    inheritAttrs: false,
});

const { t } = useI18n({ useScope: 'global' });

const select = ref<Nullable<HTMLSelectElement>>(null);
const placeholderText = computed(
    () => props.placeholder || t('components.base.formSelect.placeholder'),
);

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
            class="block font-medium text-indigo-900"
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
            class="mt-1 block w-full rounded-md border-indigo-100 bg-indigo-50 text-indigo-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-indigo-100"
            :class="{ 'cursor-not-allowed bg-indigo-100': props.disabled }"
            @change="
                emit(
                    'update:modelValue',
                    ($event.target as HTMLSelectElement).value,
                )
            "
        >
            <option v-if="props.showPlaceholder" value="" disabled>
                {{ placeholderText }}
            </option>
            <!-- Use options prop if provided, otherwise use slot -->
            <template v-if="props.options.length > 0">
                <option
                    v-for="option in props.options"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </template>
            <slot v-else />
        </select>

        <p v-if="props.helpText" class="mt-1 text-indigo-600">
            {{ props.helpText }}
        </p>

        <p v-if="props.error" class="mt-1 text-red-600">
            {{ props.error }}
        </p>
    </div>
</template>
