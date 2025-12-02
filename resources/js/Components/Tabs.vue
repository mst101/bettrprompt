<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';
import FormSelect from '@/Components/FormSelect.vue';
import type { SelectOption } from '@/types';
import { computed } from 'vue';

export interface Tab {
    id: string;
    label: string;
    icon?: string;
    mobileLabel?: string;
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
            label: tab.mobileLabel || tab.label,
        };
    });
});

const selectTab = (tabId: string) => {
    activeTab.value = tabId;
};
</script>

<template>
    <div class="">
        <!-- Mobile: Dropdown -->
        <div class="mx-4 mb-4 sm:hidden">
            <FormSelect
                id="tabs"
                v-model="activeTab"
                label="Menu"
                :options="selectOptions"
                :show-placeholder="false"
                :label-sr-only="true"
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
                        : 'border-transparent text-indigo-500 hover:border-indigo-300 hover:text-indigo-700',
                    'group inline-flex items-center border-b-2 px-1 py-4 text-sm font-medium transition-colors',
                    'focus:rounded-t-md focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-indigo-50 focus:outline-hidden',
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
                            : 'text-indigo-400 group-hover:text-indigo-600',
                        'mr-2 -ml-0.5 h-5 w-5',
                    ]"
                    aria-hidden="true"
                />
                <span>{{ tab.label }}</span>
            </button>
        </nav>
    </div>
</template>
