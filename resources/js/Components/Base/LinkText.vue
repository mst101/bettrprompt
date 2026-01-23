<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    href: string;
}

defineProps<Props>();

const linkRef = ref<{ el: HTMLAnchorElement } | null>(null);

const focus = () => {
    // Inertia Link component wraps the anchor element, so we need to access it via $el
    const element = linkRef.value?.$el as HTMLAnchorElement | undefined;
    element?.focus();
};

defineExpose({ focus });
</script>

<template>
    <Link
        ref="linkRef"
        :href="href"
        tabindex="0"
        class="rounded-md p-1 text-indigo-700 underline underline-offset-3 transition hover:text-indigo-800 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-indigo-100 focus:outline-hidden"
    >
        <slot />
    </Link>
</template>
