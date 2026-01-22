<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    title: string;
    icon: string;
    defaultExpanded?: boolean;
}>();

const storageKey = computed(
    () =>
        `admin-nav-${props.title.toLowerCase().replace(/\s+/g, '-')}-expanded`,
);
const isExpanded = ref(props.defaultExpanded ?? false);

// Load saved state from localStorage
onMounted(() => {
    const saved = localStorage.getItem(storageKey.value);
    if (saved !== null) {
        isExpanded.value = saved === 'true';
    }
});

// Save state to localStorage
watch(isExpanded, (newVal) => {
    localStorage.setItem(storageKey.value, String(newVal));
});

const toggle = () => {
    isExpanded.value = !isExpanded.value;
};

// Auto-expand if a child is active
watch(
    () => window.location.pathname,
    () => {
        // If current route is within this section, expand it
        const currentPath = window.location.pathname;
        const sectionRoutes = {
            Analytics: ['/traffic-analytics', '/domain-analytics'],
            'Users & Visitors': ['/users', '/visitors'],
            Prompts: ['/tasks', '/prompt-runs'],
            Questions: ['/questions'],
            Workflows: ['/workflows'],
            Experiments: ['/experiments'],
        };

        const routes = sectionRoutes[props.title as keyof typeof sectionRoutes];
        if (routes) {
            const isInSection = routes.some((route) =>
                currentPath.includes(route),
            );
            if (isInSection && !isExpanded.value) {
                isExpanded.value = true;
            }
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="mb-2">
        <!-- Section header -->
        <button
            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-indigo-700 transition hover:bg-indigo-50"
            @click="toggle"
        >
            <div class="flex items-center gap-3">
                <DynamicIcon :name="icon" class="h-5 w-5" />
                <span class="font-medium">{{ title }}</span>
            </div>
            <DynamicIcon
                name="chevron-down"
                :class="[
                    'h-4 w-4 transition-transform',
                    isExpanded ? 'rotate-180' : '',
                ]"
            />
        </button>

        <!-- Section items -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div v-show="isExpanded" class="mt-1 space-y-1 pl-2">
                <slot />
            </div>
        </Transition>
    </div>
</template>
