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
        <rect width="24" height="24" rx="4" fill="#CC9B7A" />
        <path
            d="M7 17.5L10.5 7h3L17 17.5h-2.5l-.7-2.5h-3.6l-.7 2.5H7zm4.1-4.5h2.8l-1.4-5-1.4 5z"
            fill="#1F1F1F"
        />
    </svg>
</template>
