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
        viewBox="0 0 512 512"
        xmlns="http://www.w3.org/2000/svg"
        fill-rule="evenodd"
        clip-rule="evenodd"
        stroke-linejoin="round"
        stroke-miterlimit="2"
    >
        <title>Meta</title>
        <path
            d="M12 0c6.627 0 12 5.373 12 12s-5.373 12-12 12S0 18.627 0 12 5.373 0 12 0zm0 3.627c-4.593 0-8.373 3.78-8.373 8.373s3.78 8.373 8.373 8.373 8.373-3.78 8.373-8.373S16.593 3.627 12 3.627z"
            fill="url(#prefix___Linear1)"
            transform="scale(21.33334)"
        ></path>
        <defs>
            <linearGradient
                id="prefix___Linear1"
                x1="0"
                y1="0"
                x2="1"
                y2="0"
                gradientUnits="userSpaceOnUse"
                gradientTransform="matrix(-24 24 -24 -24 24 0)"
            >
                <stop offset="0" stop-color="#ff97e3"></stop>
                <stop offset=".13" stop-color="#ff97e3"></stop>
                <stop offset=".18" stop-color="#d14fe1"></stop>
                <stop offset=".34" stop-color="#0050e2"></stop>
                <stop offset=".67" stop-color="#0050e2"></stop>
                <stop offset=".81" stop-color="#00ddf4"></stop>
                <stop offset=".86" stop-color="#23f8cc"></stop>
                <stop offset="1" stop-color="#23f8cc"></stop>
            </linearGradient>
        </defs>
    </svg>
</template>
