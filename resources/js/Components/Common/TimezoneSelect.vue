<script setup lang="ts">
import type { SelectOption } from '@/Types';
import { getTimezoneOptions, getTopTimezoneOptions } from '@/Utils/timezones';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';

interface Props {
    id: string;
    label: string;
    modelValue?: string;
    error?: string;
    required?: boolean;
    disabled?: boolean;
    placeholder?: string;
    helpText?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    error: '',
    required: false,
    disabled: false,
    placeholder: '',
    helpText: '',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const allOptions = computed(() => getTimezoneOptions());
const topOptions = computed(() => getTopTimezoneOptions());

const isOpen = ref(false);
const inputValue = ref(props.modelValue || '');
const activeIndex = ref(-1);
const wrapperRef = ref<HTMLElement | null>(null);
const optionRefs = ref<Array<HTMLButtonElement | null>>([]);
const listboxId = computed(() => `${props.id}-listbox`);
const optionId = (index: number) => `${props.id}-option-${index}`;

watch(
    () => props.modelValue,
    (value) => {
        if (value !== undefined && value !== inputValue.value) {
            inputValue.value = value || '';
        }
    },
);

const orderedOptions = computed(() => {
    const topValues = new Set(topOptions.value.map((option) => option.value));
    const remaining = allOptions.value.filter(
        (option) => !topValues.has(option.value),
    );

    return [...topOptions.value, ...remaining];
});

const normalizedSearch = computed(() => inputValue.value.trim().toLowerCase());

const filteredOptions = computed(() => {
    if (!normalizedSearch.value) {
        return orderedOptions.value;
    }

    return orderedOptions.value.filter((option) => {
        const label = option.label.toLowerCase();
        const value = String(option.value).toLowerCase();
        return (
            label.includes(normalizedSearch.value) ||
            value.includes(normalizedSearch.value)
        );
    });
});

watch(filteredOptions, () => {
    optionRefs.value = [];
    if (activeIndex.value >= filteredOptions.value.length) {
        activeIndex.value = filteredOptions.value.length - 1;
    }
});

watch(activeIndex, async () => {
    if (!isOpen.value || activeIndex.value < 0) {
        return;
    }

    await nextTick();
    const optionEl = optionRefs.value[activeIndex.value];
    optionEl?.scrollIntoView({ block: 'nearest' });
});

const updateValue = (value: string) => {
    inputValue.value = value;
    emit('update:modelValue', value);
};

const selectOption = (option: SelectOption) => {
    updateValue(String(option.value));
    isOpen.value = false;
    activeIndex.value = -1;
};

const handleFocus = () => {
    if (!props.disabled) {
        isOpen.value = true;
        if (activeIndex.value === -1 && filteredOptions.value.length > 0) {
            activeIndex.value = 0;
        }
    }
};

const handleBlur = () => {
    isOpen.value = false;
};

const handleKeydown = (event: KeyboardEvent) => {
    if (props.disabled) {
        return;
    }

    switch (event.key) {
        case 'ArrowDown': {
            event.preventDefault();
            if (!isOpen.value) {
                isOpen.value = true;
                activeIndex.value = 0;
                return;
            }

            const nextIndex = Math.min(
                activeIndex.value + 1,
                filteredOptions.value.length - 1,
            );
            activeIndex.value = nextIndex;
            break;
        }
        case 'ArrowUp': {
            event.preventDefault();
            if (!isOpen.value) {
                isOpen.value = true;
                activeIndex.value = 0;
                return;
            }

            const nextIndex = Math.max(activeIndex.value - 1, 0);
            activeIndex.value = nextIndex;
            break;
        }
        case 'Enter': {
            if (!isOpen.value) {
                return;
            }

            const option = filteredOptions.value[activeIndex.value];
            if (option) {
                event.preventDefault();
                selectOption(option);
            }
            break;
        }
        case 'Escape': {
            if (isOpen.value) {
                event.preventDefault();
                isOpen.value = false;
                activeIndex.value = -1;
            }
            break;
        }
        case 'Home': {
            if (!isOpen.value) {
                return;
            }
            event.preventDefault();
            activeIndex.value = 0;
            break;
        }
        case 'End': {
            if (!isOpen.value) {
                return;
            }
            event.preventDefault();
            activeIndex.value = Math.max(filteredOptions.value.length - 1, 0);
            break;
        }
    }
};

const handleClickOutside = (event: MouseEvent) => {
    if (!wrapperRef.value) {
        return;
    }

    if (!wrapperRef.value.contains(event.target as Node)) {
        isOpen.value = false;
        activeIndex.value = -1;
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});
</script>

<template>
    <div ref="wrapperRef" class="relative">
        <label :for="id" class="block font-medium text-indigo-900">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <input
            :id="id"
            :value="inputValue"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            autocomplete="off"
            role="combobox"
            :aria-controls="listboxId"
            :aria-expanded="isOpen"
            aria-autocomplete="list"
            :aria-activedescendant="
                isOpen && activeIndex >= 0 ? optionId(activeIndex) : undefined
            "
            class="mt-1 block w-full rounded-md border-indigo-100 bg-indigo-50 text-indigo-900 placeholder-indigo-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-indigo-100"
            :class="{ 'bg-indigo-100': disabled }"
            @input="updateValue(($event.target as HTMLInputElement).value)"
            @focus="handleFocus"
            @blur="handleBlur"
            @keydown="handleKeydown"
        />

        <div
            v-if="isOpen && filteredOptions.length > 0"
            :id="listboxId"
            class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-md border border-indigo-100 bg-indigo-50 shadow-lg"
            role="listbox"
        >
            <button
                v-for="(option, index) in filteredOptions"
                :id="optionId(index)"
                :key="option.value"
                :ref="(el) => (optionRefs[index] = el as HTMLButtonElement)"
                type="button"
                class="block w-full px-3 py-2 text-left text-sm text-indigo-900 hover:bg-indigo-100"
                :class="{ 'bg-indigo-100': index === activeIndex }"
                role="option"
                :aria-selected="index === activeIndex"
                @mousedown.prevent="selectOption(option)"
            >
                {{ option.label }}
            </button>
        </div>

        <p v-if="helpText" class="mt-1 text-indigo-600">
            {{ helpText }}
        </p>

        <p v-if="error" class="mt-1 text-red-600">
            {{ error }}
        </p>
    </div>
</template>
