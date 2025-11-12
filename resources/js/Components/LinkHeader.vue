<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
    href: string;
}

defineProps<Props>();
</script>

<template>
    <Link
        :href="href"
        class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-xs hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
    >
        <slot />
    </Link>
</template>
