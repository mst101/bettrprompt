<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import { computed, ref } from 'vue';

export interface AccordionItem {
    id: string;
    icon: string;
    title: string;
    subtitle: string;
    bullets: string[];
}

interface Props {
    items: AccordionItem[];
    modelValue?: string[];
    allowMultiple?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    allowMultiple: true,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string[]): void;
}>();

const expandedPanels = computed({
    get: () => props.modelValue,
    set: (value: string[]) => emit('update:modelValue', value),
});

const panelRefs = ref<Record<string, HTMLElement | null>>({});

const togglePanel = (itemId: string) => {
    const isExpanded = expandedPanels.value.includes(itemId);

    if (isExpanded) {
        // Collapse the panel
        expandedPanels.value = expandedPanels.value.filter(
            (id) => id !== itemId,
        );
    } else {
        // Expand the panel
        if (props.allowMultiple) {
            expandedPanels.value = [...expandedPanels.value, itemId];
        } else {
            expandedPanels.value = [itemId];
        }
    }
};

const handleKeydown = (event: KeyboardEvent, itemId: string, index: number) => {
    switch (event.key) {
        case 'Enter':
        case ' ':
            event.preventDefault();
            togglePanel(itemId);
            break;
        case 'ArrowDown':
            event.preventDefault();
            focusPanel(index + 1);
            break;
        case 'ArrowUp':
            event.preventDefault();
            focusPanel(index - 1);
            break;
        case 'Home':
            event.preventDefault();
            focusPanel(0);
            break;
        case 'End':
            event.preventDefault();
            focusPanel(props.items.length - 1);
            break;
    }
};

const focusPanel = (index: number) => {
    if (index < 0 || index >= props.items.length) return;
    const itemId = props.items[index].id;
    const panelRef = panelRefs.value[itemId];
    if (panelRef) {
        panelRef.focus();
    }
};

const isPanelExpanded = (itemId: string) => {
    return expandedPanels.value.includes(itemId);
};
</script>

<template>
    <div
        v-if="items.length > 0"
        role="region"
        :aria-label="$t('components.base.accordion.ariaLabel')"
        class="space-y-3"
    >
        <div
            v-for="(item, index) in items"
            :key="item.id"
            class="group overflow-hidden rounded-lg bg-white shadow dark:bg-indigo-50"
        >
            <!-- Panel Header -->
            <button
                :id="`header-${item.id}`"
                :ref="(el) => (panelRefs[item.id] = el as HTMLElement)"
                type="button"
                :aria-expanded="isPanelExpanded(item.id)"
                :aria-controls="`panel-${item.id}`"
                class="flex w-full cursor-pointer items-center justify-between gap-4 rounded-t-lg p-4 text-left transition-colors hover:bg-indigo-50 focus:ring-2 focus:ring-indigo-500 focus:outline-none focus:ring-inset"
                :class="
                    isPanelExpanded(item.id)
                        ? 'bg-indigo-50'
                        : 'bg-white focus:rounded-b-lg dark:bg-indigo-50'
                "
                @click="togglePanel(item.id)"
                @keydown="handleKeydown($event, item.id, index)"
            >
                <span
                    class="inline-flex shrink-0 items-center justify-center rounded-lg bg-indigo-100 p-3 transition-colors group-hover:dark:bg-indigo-200"
                    :class="
                        isPanelExpanded(item.id)
                            ? 'bg-indigo-50'
                            : 'bg-indigo-100 dark:bg-indigo-100'
                    "
                >
                    <DynamicIcon
                        :name="item.icon"
                        class="size-6 text-indigo-600"
                    />
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block text-lg font-semibold text-indigo-900">
                        {{ item.title }}
                    </span>
                    <!-- Subtitle -->
                    <span class="block text-indigo-700">
                        {{ item.subtitle }}
                    </span>
                </span>

                <!-- Chevron Icon -->
                <DynamicIcon
                    name="chevron-down"
                    :class="[
                        'size-5 shrink-0 text-indigo-600 transition-transform duration-300',
                        isPanelExpanded(item.id) ? 'rotate-180' : '',
                    ]"
                />
            </button>

            <!-- Panel Content -->
            <Transition
                enter-active-class="overflow-hidden transition-all duration-300"
                leave-active-class="overflow-hidden transition-all duration-300"
                enter-from-class="max-h-0 opacity-0"
                enter-to-class="max-h-[1000px] opacity-100"
                leave-from-class="max-h-[1000px] opacity-100"
                leave-to-class="max-h-0 opacity-0"
            >
                <div
                    v-if="isPanelExpanded(item.id)"
                    :id="`panel-${item.id}`"
                    role="region"
                    :aria-labelledby="`header-${item.id}`"
                    class="bg-indigo-50 p-4 pt-0"
                >
                    <!-- Bullets -->
                    <ul
                        class="list-inside list-disc space-y-1.5 pt-2 text-indigo-900"
                    >
                        <li
                            v-for="(bullet, bulletIndex) in item.bullets"
                            :key="bulletIndex"
                        >
                            {{ bullet }}
                        </li>
                    </ul>
                </div>
            </Transition>
        </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center text-indigo-700">
        <p>{{ $t('components.base.accordion.empty') }}</p>
    </div>
</template>
