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
        <circle cx="12" cy="12" r="11" fill="#0078D4" />
        <path
            d="M12 6a6 6 0 0 0-6 6c0 1.5.6 2.9 1.5 4l1.5-1.5c-.6-.7-1-1.6-1-2.5a4 4 0 0 1 8 0c0 .9-.4 1.8-1 2.5l1.5 1.5c.9-1.1 1.5-2.5 1.5-4a6 6 0 0 0-6-6zm0 4a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"
            fill="white"
        />
        <path d="M12 14l-3 5h6l-3-5z" fill="white" />
    </svg>
</template>
