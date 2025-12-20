<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: string;
}>();
</script>

<template>
    <Link
        :href="href"
        class="block w-full rounded-md px-4 py-2 text-start text-sm leading-5 text-indigo-700 transition duration-150 ease-in-out hover:bg-indigo-50 focus:bg-indigo-50 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden sm:text-base"
    >
        <slot />
    </Link>
</template>
