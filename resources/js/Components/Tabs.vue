<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';
import { computed } from 'vue';

export interface Tab {
    id: string;
    label: string;
    icon?: string;
    badge?: string | number;
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

const selectTab = (tabId: string) => {
    activeTab.value = tabId;
};
</script>

<template>
    <div class="border-b border-gray-200">
        <!-- Mobile: Dropdown -->
        <div class="sm:hidden">
            <label for="tabs" class="sr-only">Select a tab</label>
            <select
                id="tabs"
                name="tabs"
                :value="activeTab"
                class="block w-full rounded-md border-gray-300 py-2 pr-10 pl-3 text-base focus:border-indigo-500 focus:ring-indigo-500 focus:outline-hidden sm:text-sm"
                @change="selectTab(($event.target as HTMLSelectElement).value)"
            >
                <option v-for="tab in tabs" :key="tab.id" :value="tab.id">
                    {{ tab.label }}
                    <span v-if="tab.badge"> ({{ tab.badge }})</span>
                </option>
            </select>
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
                    'focus:rounded-t-md focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden',
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
                            : 'text-gray-400 group-hover:text-gray-500',
                        'mr-2 -ml-0.5 h-5 w-5',
                    ]"
                    aria-hidden="true"
                />
                <span>{{ tab.label }}</span>
                <span
                    v-if="tab.badge"
                    :class="[
                        'ml-3 inline-block rounded-full px-2.5 py-0.5 text-xs font-medium',
                        activeTab === tab.id
                            ? 'bg-indigo-100 text-indigo-600'
                            : 'bg-gray-100 text-gray-900',
                    ]"
                >
                    {{ tab.badge }}
                </span>
            </button>
        </nav>
    </div>
</template>
