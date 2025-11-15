<script setup lang="ts">
interface Props {
    class?: string;
}

withDefaults(defineProps<Props>(), {
    class: 'h-8 w-8',
});
</script>

<template>
    <svg
        :class="$props.class"
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
    >
        <rect width="24" height="24" rx="4" fill="#20808D" />
        <path d="M6 6h12v2H6V6zm0 5h12v2H6v-2zm0 5h12v2H6v-2z" fill="white" />
        <circle cx="18" cy="7" r="1.5" fill="#1FBCAA" />
        <circle cx="18" cy="12" r="1.5" fill="#1FBCAA" />
        <circle cx="18" cy="17" r="1.5" fill="#1FBCAA" />
    </svg>
</template>
