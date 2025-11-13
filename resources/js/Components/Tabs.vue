<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';

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

// Scroll shadow indicators
const navRef = ref<HTMLElement | null>(null);
const showLeftShadow = ref(false);
const showRightShadow = ref(false);

const updateScrollShadows = () => {
    if (!navRef.value) return;

    const { scrollLeft, scrollWidth, clientWidth } = navRef.value;

    // Show left shadow if scrolled right
    showLeftShadow.value = scrollLeft > 0;

    // Show right shadow if not scrolled to the end
    showRightShadow.value = scrollLeft < scrollWidth - clientWidth - 1;
};

onMounted(() => {
    if (navRef.value) {
        navRef.value.addEventListener('scroll', updateScrollShadows);
        // Initial check
        updateScrollShadows();

        // Check again after a short delay (in case of dynamic content)
        setTimeout(updateScrollShadows, 100);
    }

    // Also update on window resize
    window.addEventListener('resize', updateScrollShadows);
});

onUnmounted(() => {
    if (navRef.value) {
        navRef.value.removeEventListener('scroll', updateScrollShadows);
    }
    window.removeEventListener('resize', updateScrollShadows);
});
</script>

<template>
    <div class="relative border-b border-gray-200">
        <!-- Left shadow indicator -->
        <div
            class="pointer-events-none absolute top-0 left-0 z-10 h-full w-8 bg-gradient-to-r from-white to-transparent opacity-0 transition-opacity"
            :class="{ 'opacity-100': showLeftShadow }"
            aria-hidden="true"
        ></div>

        <!-- Scrollable tabs container -->
        <nav
            ref="navRef"
            class="scrollbar-hide -mb-px flex snap-x snap-mandatory space-x-8 overflow-x-auto scroll-smooth p-1"
            aria-label="Tabs"
        >
            <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="selectTab(tab.id)"
                :class="[
                    activeTab === tab.id
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                    'group inline-flex shrink-0 snap-start items-center border-b-2 px-1 py-3 text-sm font-medium transition-colors',
                    'focus:rounded-t-md focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden',
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
                        'ml-3 inline-block rounded-full px-2.5 py-0.5 text-xs font-medium',
                    ]"
                >
                    {{ tab.badge }}
                </span>
            </button>
        </nav>

        <!-- Right shadow indicator -->
        <div
            class="pointer-events-none absolute top-0 right-0 z-10 h-full w-8 bg-gradient-to-l from-white to-transparent opacity-0 transition-opacity"
            :class="{ 'opacity-100': showRightShadow }"
            aria-hidden="true"
        ></div>
    </div>
</template>
