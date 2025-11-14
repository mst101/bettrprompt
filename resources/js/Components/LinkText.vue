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
        class="rounded-md p-1 text-sm text-indigo-600 underline underline-offset-3 transition hover:text-indigo-800 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-gray-100 focus:outline-hidden"
    >
        <slot />
    </Link>
</template>
