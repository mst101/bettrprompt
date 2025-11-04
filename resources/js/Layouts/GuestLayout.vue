<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0"
    >
        <div>
            <Link
                href="/"
                class="flex items-center gap-3 transition hover:opacity-80"
            >
                <ApplicationLogo
                    class="h-16 w-16 fill-current text-indigo-600"
                />
                <span class="text-3xl font-bold text-gray-800">AI Buddy</span>
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
