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
        <defs>
            <linearGradient
                id="gemini-gradient"
                x1="0%"
                y1="0%"
                x2="100%"
                y2="100%"
            >
                <stop
                    offset="0%"
                    style="stop-color: #4285f4; stop-opacity: 1"
                />
                <stop
                    offset="50%"
                    style="stop-color: #9b72cb; stop-opacity: 1"
                />
                <stop
                    offset="100%"
                    style="stop-color: #d96570; stop-opacity: 1"
                />
            </linearGradient>
        </defs>
        <path
            d="M12 2L3 7v10l9 5 9-5V7l-9-5zm0 2.18L18.82 8 12 11.82 5.18 8 12 4.18zM5 9.82l6 3.5V20.32l-6-3.5V9.82zm8 10.5v-7l6-3.5v6.99l-6 3.51z"
            fill="url(#gemini-gradient)"
        />
    </svg>
</template>
