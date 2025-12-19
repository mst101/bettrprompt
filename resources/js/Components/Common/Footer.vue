<script setup lang="ts">
import ButtonText from '@/Components/Base/Button/ButtonText.vue';
import { ref } from 'vue';
import CookieSettings from './CookieSettings.vue';
import LinkText from './LinkText.vue';

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
                    © {{ new Date().getFullYear() }} BettrPrompt. All rights
                    reserved.
                </div>

                <!-- Links -->
                <div class="flex flex-wrap justify-center gap-6">
                    <LinkText :href="route('terms')">Terms of Use</LinkText>
                    <LinkText :href="route('privacy')">Privacy Policy</LinkText>
                    <LinkText :href="route('cookies')">Cookie Policy</LinkText>
                    <ButtonText
                        id="cookie-settings"
                        type="button"
                        @click="openCookieSettings"
                    >
                        Cookie Settings
                    </ButtonText>
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
