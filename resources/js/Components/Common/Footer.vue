<script setup lang="ts">
import ButtonText from '@/Components/Base/Button/ButtonText.vue';
import LinkText from '@/Components/Base/LinkText.vue';
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
    <footer class="border-t border-indigo-100 bg-white dark:border-indigo-50">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div
                class="flex flex-col items-center justify-between gap-4 sm:flex-row"
            >
                <!-- Copyright -->
                <div
                    class="text-center text-xs text-indigo-800 sm:text-left sm:text-sm"
                >
                    {{
                        $t('components.common.footer.copyright', {
                            year: new Date().getFullYear(),
                        })
                    }}
                </div>

                <!-- Links -->
                <div class="flex flex-wrap justify-center gap-6">
                    <LinkText :href="countryRoute('terms')">{{
                        $t('components.common.footer.terms')
                    }}</LinkText>
                    <LinkText :href="countryRoute('privacy')">{{
                        $t('components.common.footer.privacy')
                    }}</LinkText>
                    <LinkText :href="countryRoute('cookies')">{{
                        $t('components.common.footer.cookies')
                    }}</LinkText>
                    <ButtonText
                        id="cookie-settings"
                        type="button"
                        @click="openCookieSettings"
                    >
                        {{ $t('components.common.footer.cookieSettings') }}
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
