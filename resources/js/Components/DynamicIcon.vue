<script setup lang="ts">
import { computed, defineAsyncComponent, type Component } from 'vue';

interface Props {
    name: string;
}

const props = defineProps<Props>();

// Use Vite's glob import for dynamic loading of icons
const iconModules = import.meta.glob<{ default: Component }>(
    '@/Icons/Svg*.vue',
);

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
    'building-office': 'building-storefront',
};

const getIconPath = (iconName: string): string => {
    // Check if there's an alias for this icon
    const resolvedName = iconAliases[iconName] || iconName;
    const pascalName = kebabToPascal(resolvedName);
    return `/resources/js/Icons/Svg${pascalName}.vue`;
};

const iconComponent = computed(() => {
    const iconPath = getIconPath(props.name);

    // Find the matching module
    const moduleKey = Object.keys(iconModules).find((key) =>
        key.endsWith(iconPath),
    );

    if (!moduleKey) {
        console.warn(`Icon "${props.name}" not found`);
        return null;
    }

    // Return an async component with a loading state
    return defineAsyncComponent({
        loader: iconModules[moduleKey],
        loadingComponent: {
            template: '<div class="size-5 animate-pulse rounded bg-gray-200" />',
        },
        errorComponent: {
            template: '<div class="size-5 rounded bg-gray-200" />',
        },
        delay: 0, // Show loading immediately
        timeout: 3000, // Timeout after 3 seconds
    });
});
</script>

<template>
    <!-- Forward all attributes (class, style, size, etc.) to the SVG root -->
    <component :is="iconComponent" v-if="iconComponent" v-bind="$attrs" />
    <div v-else class="size-5 rounded bg-gray-200" />
</template>
