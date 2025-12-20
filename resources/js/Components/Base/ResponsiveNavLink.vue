<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    href: string;
    active?: boolean;
}>();

const emit = defineEmits<{
    click: [];
}>();

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-indigo-400 text-start font-medium text-indigo-700 bg-indigo-50 focus:outline-hidden focus:text-indigo-800 focus:bg-indigo-100 focus:border-indigo-700 transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start font-medium text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 hover:border-indigo-300 focus:outline-hidden focus:text-indigo-800 focus:bg-indigo-50 focus:border-indigo-300 transition duration-150 ease-in-out',
);

const handleClick = () => {
    emit('click');
};

// Expose the link element for parent components to focus
const linkRef = ref<InstanceType<typeof Link> | null>(null);

const focus = () => {
    // Access the underlying anchor element from the Inertia Link component
    const anchorElement = linkRef.value?.$el as HTMLAnchorElement | undefined;
    anchorElement?.focus();
};

defineExpose({
    focus,
});
</script>

<template>
    <Link ref="linkRef" :href="href" :class="classes" @click="handleClick">
        <slot />
    </Link>
</template>
