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
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="selectTab(tab.id)"
                :class="[
                    activeTab === tab.id
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                    'group inline-flex items-center border-b-2 px-1 py-4 text-sm font-medium transition-colors',
                ]"
                :aria-current="activeTab === tab.id ? 'page' : undefined"
            >
                <DynamicIcon
                    v-if="tab.icon"
                    :name="tab.icon"
                    :class="[
                        activeTab === tab.id
                            ? 'text-indigo-500'
                            : 'text-gray-400 group-hover:text-gray-500',
                        'mr-2 -ml-0.5 h-5 w-5 transition-colors',
                    ]"
                    aria-hidden="true"
                />
                <span>{{ tab.label }}</span>
                <span
                    v-if="tab.badge"
                    :class="[
                        activeTab === tab.id
                            ? 'bg-indigo-100 text-indigo-600'
                            : 'bg-gray-100 text-gray-900',
                        'ml-3 hidden rounded-full px-2.5 py-0.5 text-xs font-medium md:inline-block',
                    ]"
                >
                    {{ tab.badge }}
                </span>
            </button>
        </nav>
    </div>
</template>
