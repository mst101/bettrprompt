<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    href: string;
    label: string;
    active?: boolean;
    routeName?: string;
}>();

// Note: route() is a global function provided by Inertia.js/Ziggy
// It's declared in resources/js/Types/global.d.ts
declare const route: {
    (name: string, params?: Record<string, any>): string;
    current(name?: string, params?: Record<string, any>): boolean | string;
};

// Auto-detect active state based on route name or explicit prop
const isActive = computed(() => {
    if (props.active !== undefined) return props.active;
    if (props.routeName) {
        return route().current(props.routeName);
    }
    return false;
});
</script>

<template>
    <Link
        :href="href"
        :class="[
            'block rounded-lg px-3 py-2 text-sm transition',
            isActive
                ? 'border-l-4 border-indigo-600 bg-indigo-100 pl-2 text-indigo-900'
                : 'pl-3 text-indigo-700 hover:bg-indigo-50 hover:text-indigo-800',
        ]"
    >
        {{ label }}
    </Link>
</template>
