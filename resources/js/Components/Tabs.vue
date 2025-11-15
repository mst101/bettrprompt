<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';
import FormSelect from '@/Components/FormSelect.vue';
import type { SelectOption } from '@/types';
import { computed } from 'vue';

export interface Tab {
    id: string;
    label: string;
    icon?: string;
}

interface Props {
    tabs: Tab[];
    modelValue: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const activeTab = computed({
    get: () => props.modelValue,
    set: (value: string) => emit('update:modelValue', value),
});

const selectOptions = computed<SelectOption[]>(() => {
    return props.tabs.map((tab) => {
        return {
            value: tab.id,
            label: tab.label,
        };
    });
});

const selectTab = (tabId: string) => {
    activeTab.value = tabId;
};
</script>

<template>
    <div class="border-b border-gray-200 px-6 pt-6">
        <!-- Mobile: Dropdown -->
        <div class="sm:hidden">
            <FormSelect
                id="tabs"
                v-model="activeTab"
                label="Menu"
                :options="selectOptions"
                :show-placeholder="false"
                class="[&_label]:sr-only"
            />
        </div>

        <!-- Desktop: Horizontal Tabs (sm: and above) -->
        <nav class="-mb-px hidden flex-wrap gap-x-8 sm:flex" aria-label="Tabs">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                :tabindex="activeTab === tab.id ? -1 : 0"
                :class="[
                    activeTab === tab.id
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                    'group inline-flex items-center border-b-2 px-1 py-4 text-sm font-medium transition-colors',
                    'focus:rounded-t-md focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100 focus:outline-hidden',
                ]"
                :aria-current="activeTab === tab.id ? 'page' : undefined"
                @click="selectTab(tab.id)"
            >
                <DynamicIcon
                    v-if="tab.icon"
                    :name="tab.icon"
                    :class="[
                        activeTab === tab.id
                            ? 'text-indigo-500'
                            : 'text-gray-400 group-hover:text-gray-600',
                        'mr-2 -ml-0.5 h-5 w-5',
                    ]"
                    aria-hidden="true"
                />
                <span>{{ tab.label }}</span>
            </button>
        </nav>
    </div>
</template>
