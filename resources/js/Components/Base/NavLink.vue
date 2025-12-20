<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    href: string;
    active?: boolean;
}>();

const classes = computed(() =>
    props.active
        ? 'border-indigo-400 text-indigo-900'
        : 'border-transparent text-indigo-700 hover:text-indigo-800 focus:text-indigo-800',
);
</script>

<template>
    <Link
        :href="href"
        class="rounded-md p-3 leading-5 font-medium transition duration-150 ease-in-out hover:bg-indigo-50 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden focus:outline-none"
        :class="classes"
    >
        <slot />
    </Link>
</template>
