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
                id="meta-gradient"
                x1="0%"
                y1="0%"
                x2="100%"
                y2="100%"
            >
                <stop
                    offset="0%"
                    style="stop-color: #0668e1; stop-opacity: 1"
                />
                <stop
                    offset="50%"
                    style="stop-color: #0a7cff; stop-opacity: 1"
                />
                <stop
                    offset="100%"
                    style="stop-color: #00c6ff; stop-opacity: 1"
                />
            </linearGradient>
        </defs>
        <circle cx="12" cy="12" r="11" fill="url(#meta-gradient)" />
        <path
            d="M7 8h3l2 5 2-5h3l-3.5 8h-3L7 8zm10 8V8h2v8h-2zM5 8h2v8H5V8z"
            fill="white"
        />
    </svg>
</template>
