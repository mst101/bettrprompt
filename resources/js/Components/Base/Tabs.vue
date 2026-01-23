<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import type { SelectOption } from '@/Types';
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

const handleKeyDown = (event: KeyboardEvent) => {
    const currentIndex = props.tabs.findIndex(
        (tab) => tab.id === activeTab.value,
    );
    if (currentIndex === -1) return;

    let nextIndex: number | null = null;

    switch (event.key) {
        case 'ArrowRight':
            nextIndex = (currentIndex + 1) % props.tabs.length;
            event.preventDefault();
            break;
        case 'ArrowLeft':
            nextIndex =
                (currentIndex - 1 + props.tabs.length) % props.tabs.length;
            event.preventDefault();
            break;
        case 'Home':
            nextIndex = 0;
            event.preventDefault();
            break;
        case 'End':
            nextIndex = props.tabs.length - 1;
            event.preventDefault();
            break;
    }

    if (nextIndex !== null) {
        selectTab(props.tabs[nextIndex].id);
    }
};
</script>

<template>
    <div class="">
        <!-- Mobile: Dropdown -->
        <div class="mx-4 mb-4 sm:hidden">
            <FormSelect
                id="tabs"
                v-model="activeTab"
                :label="$t('components.base.tabs.menuLabel')"
                :options="selectOptions"
                :show-placeholder="false"
                :label-sr-only="true"
            />
        </div>

        <!-- Desktop: Horizontal Tabs (sm: and above) -->
        <nav
            class="-mb-px hidden flex-wrap sm:flex"
            :class="tabs.length > 6 ? 'gap-x-5' : 'gap-x-8'"
            :aria-label="$t('components.base.tabs.ariaLabel')"
        >
            <button
                v-for="tab in tabs"
                :key="tab.id"
                :tabindex="activeTab === tab.id ? 0 : -1"
                :data-testid="`tab-button-${tab.id}`"
                :class="[
                    activeTab === tab.id
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-indigo-500 hover:border-indigo-100 hover:text-indigo-700',
                    'group inline-flex items-center border-b-2 px-1 py-4 text-xs font-medium transition-colors sm:text-sm',
                    'focus:rounded-t-md focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-indigo-50 focus:outline-hidden',
                ]"
                :aria-current="activeTab === tab.id ? 'page' : undefined"
                @click="selectTab(tab.id)"
                @keydown="handleKeyDown"
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
