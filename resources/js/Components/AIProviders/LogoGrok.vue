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
        <rect width="24" height="24" rx="4" fill="#000000" />
        <path
            d="M12 4L4 8v8l8 4 8-4V8l-8-4zm0 2.5l5 2.5v5l-5 2.5-5-2.5V9l5-2.5z"
            fill="white"
        />
        <circle cx="12" cy="12" r="2" fill="white" />
    </svg>
</template>
