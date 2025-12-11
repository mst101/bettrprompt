<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
    href: string;
    title: string;
    subtitle: string;
    description: string;
    icon?: string;
    colorScheme?: 'indigo' | 'amber';
}

withDefaults(defineProps<Props>(), {
    icon: '→',
    colorScheme: 'indigo',
});
</script>

<template>
    <Link
        :href="href"
        class="group overflow-hidden rounded-lg border p-6 shadow-sm transition hover:shadow-lg"
        :class="{
            'bg-white hover:bg-indigo-100 dark:bg-indigo-100 dark:hover:bg-indigo-200':
                colorScheme === 'indigo',
            'bg-amber-50 hover:bg-amber-100': colorScheme === 'amber',
        }"
    >
        <div class="flex items-start justify-between">
            <div>
                <h2
                    class="text-xl font-semibold transition"
                    :class="{
                        'text-indigo-900': colorScheme === 'indigo',
                        'text-amber-900': colorScheme === 'amber',
                    }"
                >
                    {{ title }}
                </h2>
                <p
                    class="mt-2 text-sm"
                    :class="{
                        'text-indigo-800': colorScheme === 'indigo',
                        'text-amber-800': colorScheme === 'amber',
                    }"
                >
                    {{ subtitle }}
                </p>
                <p
                    class="mt-1 text-xs"
                    :class="{
                        'text-indigo-700': colorScheme === 'indigo',
                        'text-amber-700': colorScheme === 'amber',
                    }"
                >
                    {{ description }}
                </p>
            </div>
            <div class="text-2xl text-indigo-900">{{ icon }}</div>
        </div>
    </Link>
</template>
