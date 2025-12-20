<script setup lang="ts">
import { useButtonClasses } from '@/Composables/ui/useButtonClasses';
import { Link } from '@inertiajs/vue3';

type ButtonVariant = 'primary' | 'secondary' | 'danger';
type ButtonSize = 'sm' | 'md' | 'lg';

interface Props {
    id?: string;
    href: string;
    size?: ButtonSize;
    variant?: ButtonVariant;
}

const props = withDefaults(defineProps<Props>(), {
    id: undefined,
    variant: 'primary',
    size: 'md',
});

const buttonClasses = useButtonClasses(props);
</script>

<template>
    <Link :id="id" :href="href" :class="buttonClasses">
        <slot />
    </Link>
</template>
