<script setup lang="ts">
import { computed, type Component } from 'vue';

// Pre-import commonly used icons to avoid async overhead
import SvgArrowRight from '@/Icons/SvgArrowRight.vue';
import SvgCheck from '@/Icons/SvgCheck.vue';
import SvgCheckCircle from '@/Icons/SvgCheckCircle.vue';
import SvgChevronDown from '@/Icons/SvgChevronDown.vue';
import SvgChevronLeft from '@/Icons/SvgChevronLeft.vue';
import SvgChevronRight from '@/Icons/SvgChevronRight.vue';
import SvgClock from '@/Icons/SvgClock.vue';
import SvgEdit from '@/Icons/SvgEdit.vue';
import SvgExclamationCircle from '@/Icons/SvgExclamationCircle.vue';
import SvgInformationCircle from '@/Icons/SvgInformationCircle.vue';
import SvgMoon from '@/Icons/SvgMoon.vue';
import SvgSun from '@/Icons/SvgSun.vue';
import SvgXMark from '@/Icons/SvgXMark.vue';

interface Props {
    name: string;
}

const props = defineProps<Props>();

// Pre-imported common icons - loaded synchronously (no async boundary)
const preImportedIcons: Record<string, Component> = {
    'arrow-right': SvgArrowRight,
    check: SvgCheck,
    'check-circle': SvgCheckCircle,
    'chevron-down': SvgChevronDown,
    'chevron-left': SvgChevronLeft,
    'chevron-right': SvgChevronRight,
    clock: SvgClock,
    edit: SvgEdit,
    'exclamation-circle': SvgExclamationCircle,
    'information-circle': SvgInformationCircle,
    moon: SvgMoon,
    sun: SvgSun,
    'x-mark': SvgXMark,
};

// Dynamically load rare icons on demand
const dynamicIconModules = {
    ...import.meta.glob<{ default: Component }>('@/Icons/Svg*.vue'),
    ...import.meta.glob<{ default: Component }>('@/Components/Logos/*.vue'),
};

// Convert kebab-case icon name to PascalCase component name
const kebabToPascal = (str: string): string => {
    return str
        .split('-')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join('');
};

// Map icon names to their file paths
const iconAliases: Record<string, string> = {
    'cog-6-tooth': 'cog',
    settings: 'cog',
    search: 'magnifying-glass',
    spinner: 'arrow-path-spin',
};

const getIconPath = (iconName: string): string => {
    // Check if there's an alias for this icon
    const resolvedName = iconAliases[iconName] || iconName;
    const pascalName = kebabToPascal(resolvedName);
    return `Svg${pascalName}.vue`;
};

const iconComponent = computed(() => {
    // Check pre-imported icons first (no async overhead)
    if (preImportedIcons[props.name]) {
        return preImportedIcons[props.name];
    }

    // Fall back to dynamic loading for rare icons
    const iconPath = getIconPath(props.name);

    // Find the matching module
    const moduleKey = Object.keys(dynamicIconModules).find((key) =>
        key.endsWith(iconPath),
    );

    if (!moduleKey) {
        console.warn(`Icon "${props.name}" not found`);
        return null;
    }

    // Return the module directly without async wrapper to reduce overhead
    return dynamicIconModules[moduleKey].default;
});
</script>

<template>
    <!-- Forward all attributes (class, style, size, etc.) to the SVG root -->
    <component :is="iconComponent" v-if="iconComponent" v-bind="$attrs" />
    <div v-else class="size-5 rounded-sm bg-indigo-200" />
</template>
