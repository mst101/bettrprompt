<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <footer class="border-t border-gray-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div
                class="flex flex-col items-center justify-between gap-4 sm:flex-row"
            >
                <!-- Copyright -->
                <div class="text-center text-sm text-gray-600 sm:text-left">
                    © {{ new Date().getFullYear() }} AI Buddy. All rights
                    reserved.
                </div>

                <!-- Links -->
                <div class="flex flex-wrap justify-center gap-6">
                    <Link
                        :href="route('terms')"
                        class="text-sm text-gray-600 transition hover:text-indigo-600"
                    >
                        Terms of Use
                    </Link>
                    <Link
                        :href="route('privacy')"
                        class="text-sm text-gray-600 transition hover:text-indigo-600"
                    >
                        Privacy Policy
                    </Link>
                </div>
            </div>
        </div>
    </footer>
</template>
