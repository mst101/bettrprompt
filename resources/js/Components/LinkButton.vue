<script setup lang="ts">
import Button from '@/Components/Button.vue';
import { Link } from '@inertiajs/vue3';

type ButtonVariant = 'primary' | 'secondary' | 'danger';
type ButtonSize = 'sm' | 'md' | 'lg';

interface Props {
    id?: string;
    href: string;
    size?: ButtonSize;
    variant?: ButtonVariant;
}

withDefaults(defineProps<Props>(), {
    id: undefined,
    variant: 'primary',
    size: 'md',
});
</script>

<template>
    <Link :id="id" :href="href">
        <Button :variant="variant" :size="size" type="button">
            <slot />
        </Button>
    </Link>
</template>
