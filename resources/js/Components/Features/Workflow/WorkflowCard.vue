<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
    href: string;
    title: string;
    subtitle: string;
    description: string;
    icon?: string;
}

withDefaults(defineProps<Props>(), {
    icon: '→',
});
</script>

<template>
    <Link
        :href="href"
        class="group overflow-hidden rounded-lg border bg-white p-6 shadow-sm transition hover:bg-indigo-100 hover:shadow-lg dark:bg-indigo-100 dark:hover:bg-indigo-200"
    >
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-semibold text-indigo-900 transition">
                    {{ title }}
                </h2>
                <p class="mt-2 text-sm text-indigo-800">
                    {{ subtitle }}
                </p>
                <p class="mt-1 text-xs text-indigo-700">
                    {{ description }}
                </p>
            </div>
            <div class="text-2xl text-indigo-900">{{ icon }}</div>
        </div>
    </Link>
</template>
