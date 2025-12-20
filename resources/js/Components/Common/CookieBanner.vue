<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import LinkText from '@/Components/Base/LinkText.vue';
import { useCookieConsent } from '@/Composables/features/useCookieConsent';
import { ref } from 'vue';
import CookieSettings from './CookieSettings.vue';

const { hasConsent, acceptAll, rejectAll } = useCookieConsent();
const showSettings = ref(false);

const openSettings = () => {
    showSettings.value = true;
};

const closeSettings = () => {
    showSettings.value = false;
};
</script>

<template>
    <Teleport to="body">
        <!-- Cookie Banner -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-full opacity-0"
        >
            <div
                v-if="!hasConsent"
                class="pb-safe fixed inset-x-0 bottom-0 z-50"
                role="dialog"
                aria-modal="false"
                aria-label="Cookie consent banner"
            >
                <div class="bg-gray-800">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        <div
                            class="lg:flex lg:items-center lg:justify-between lg:gap-8"
                        >
                            <div class="flex-1">
                                <h2 class="text-lg font-semibold text-white">
                                    We use cookies
                                </h2>
                                <p class="mt-2 text-sm text-gray-300">
                                    We use essential cookies to make our site
                                    work. With your consent, we may also use
                                    non-essential cookies to improve user
                                    experience and analyse website traffic. By
                                    clicking "Accept All", you agree to our use
                                    of cookies.
                                    <LinkText :href="route('cookies')">
                                        Read our Cookie Policy
                                    </LinkText>
                                </p>
                            </div>
                            <div
                                class="mt-6 flex flex-col gap-3 sm:flex-row lg:mt-0 lg:shrink-0"
                            >
                                <ButtonSecondary
                                    class="focus:ring-offset-gray-900"
                                    type="button"
                                    @click="rejectAll"
                                >
                                    Reject All
                                </ButtonSecondary>
                                <ButtonSecondary
                                    class="focus:ring-offset-gray-900"
                                    type="button"
                                    @click="openSettings"
                                >
                                    Customise
                                </ButtonSecondary>
                                <ButtonPrimary
                                    class="focus:ring-offset-gray-900"
                                    type="button"
                                    @click="acceptAll"
                                >
                                    Accept All
                                </ButtonPrimary>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Cookie Settings Modal -->
        <CookieSettings :show="showSettings" @close="closeSettings" />
    </Teleport>
</template>
