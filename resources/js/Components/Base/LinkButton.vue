<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import { useButtonClasses } from '@/Composables/ui/useButtonClasses';
import { Link } from '@inertiajs/vue3';

type ButtonVariant = 'primary' | 'secondary' | 'danger';
type ButtonSize = 'sm' | 'md' | 'lg';
type IconPosition = 'left' | 'right';

interface Props {
    id?: string;
    href: string;
    size?: ButtonSize;
    variant?: ButtonVariant;
    icon?: string;
    iconPosition?: IconPosition;
}

const props = withDefaults(defineProps<Props>(), {
    id: undefined,
    variant: 'primary',
    size: 'md',
    icon: undefined,
    iconPosition: 'right',
});

const buttonClasses = useButtonClasses(props);
</script>

<template>
    <Link :id="id" :href="href" :class="buttonClasses">
        <span class="flex items-center">
            <!-- Left icon -->
            <span
                v-if="icon && iconPosition === 'left'"
                class="mr-2 -ml-1 h-4 w-4 flex-shrink-0"
            >
                <DynamicIcon :name="icon" class="h-4 w-4" />
            </span>
            <slot />
            <!-- Right icon -->
            <span
                v-if="icon && iconPosition === 'right'"
                class="ml-2 h-4 w-4 flex-shrink-0"
            >
                <DynamicIcon :name="icon" class="h-4 w-4" />
            </span>
        </span>
    </Link>
</template>
