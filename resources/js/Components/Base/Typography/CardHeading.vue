<script setup lang="ts">
interface Props {
    title: string;
    level?: 2 | 3 | 4;
}

withDefaults(defineProps<Props>(), {
    level: 3,
});

const headingTag = (level: 2 | 3 | 4) => {
    switch (level) {
        case 2:
            return 'h2';
        case 3:
            return 'h3';
        case 4:
            return 'h4';
        default:
            return 'h3';
    }
};
</script>

<template>
    <component
        :is="headingTag(level)"
        class="text-lg font-semibold text-indigo-900"
    >
        {{ title }}
    </component>
</template>
