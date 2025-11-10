<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import CookieSettings from './CookieSettings.vue';

const showCookieSettings = ref(false);

const openCookieSettings = () => {
    showCookieSettings.value = true;
};

const closeCookieSettings = () => {
    showCookieSettings.value = false;
};
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
                        class="rounded-md p-1 text-sm text-gray-600 transition hover:text-indigo-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    >
                        Terms of Use
                    </Link>
                    <Link
                        :href="route('privacy')"
                        class="rounded-md p-1 text-sm text-gray-600 transition hover:text-indigo-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    >
                        Privacy Policy
                    </Link>
                    <Link
                        :href="route('cookies')"
                        class="rounded-md p-1 text-sm text-gray-600 transition hover:text-indigo-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    >
                        Cookie Policy
                    </Link>
                    <button
                        @click="openCookieSettings"
                        type="button"
                        class="rounded-md p-1 text-sm text-gray-600 transition hover:text-indigo-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    >
                        Cookie Settings
                    </button>
                </div>
            </div>
        </div>
    </footer>

    <!-- Cookie Settings Modal -->
    <Teleport to="body">
        <CookieSettings
            :show="showCookieSettings"
            @close="closeCookieSettings"
        />
    </Teleport>
</template>
