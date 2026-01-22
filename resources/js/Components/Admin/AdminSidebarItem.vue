<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    href: string;
    label: string;
    active?: boolean;
}>();

const page = usePage();
const activeState = ref(false);

// Determine if this link is active by comparing current URL with href
function updateActiveState(): void {
    // Only use explicit active prop if it's true (for forcing a match)
    // Regular items should always use URL comparison
    if (props.active === true) {
        activeState.value = true;
        return;
    }

    // Get the current URL from Inertia's page object (without query string)
    const currentUrl = page.url.split('?')[0].replace(/\/$/, '');

    // Extract path from href (countryRoute returns full URL like https://app.localhost/gb/admin/users)
    let hrefUrl = props.href;
    try {
        const url = new URL(props.href, window.location.origin);
        hrefUrl = url.pathname;
    } catch {
        // If it's already a path, use it as-is
    }
    hrefUrl = hrefUrl.replace(/\/$/, '');

    activeState.value = currentUrl === hrefUrl;
}

// Watch for page URL changes and update active state
watch(
    () => page.url,
    () => {
        updateActiveState();
    },
    { immediate: true },
);

const isActive = computed(() => activeState.value);
</script>

<template>
    <Link
        :href="href"
        :class="[
            'block rounded-lg px-3 py-2 text-sm transition',
            isActive
                ? 'bg-indigo-50 pl-2 text-indigo-900'
                : 'pl-2 text-indigo-700 hover:bg-indigo-50 hover:text-indigo-800',
        ]"
    >
        {{ label }}
    </Link>
</template>
