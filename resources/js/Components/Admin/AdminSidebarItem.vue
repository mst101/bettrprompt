<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    href: string;
    label: string;
    active?: boolean;
}>();

// Auto-detect active state by comparing URLs (accounts for country prefix)
const isActive = computed(() => {
    if (props.active !== undefined) return props.active;
    // Compare the href (generated with countryRoute) with current pathname
    return window.location.pathname === props.href;
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
