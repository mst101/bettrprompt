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
                :aria-label="$t('components.common.cookieBanner.ariaLabel')"
            >
                <div class="bg-indigo-800">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        <div
                            class="lg:flex lg:items-center lg:justify-between lg:gap-8"
                        >
                            <div class="flex-1">
                                <h2
                                    class="text-base font-semibold text-white sm:text-lg"
                                >
                                    {{
                                        $t(
                                            'components.common.cookieBanner.title',
                                        )
                                    }}
                                </h2>
                                <p
                                    class="mt-2 text-xs text-indigo-300 sm:text-sm"
                                >
                                    {{
                                        $t(
                                            'components.common.cookieBanner.description',
                                        )
                                    }}
                                    <LinkText
                                        class="text-indigo-200! hover:text-indigo-300!"
                                        :href="countryRoute('cookies')"
                                    >
                                        {{
                                            $t(
                                                'components.common.cookieBanner.policyLink',
                                            )
                                        }}
                                    </LinkText>
                                </p>
                            </div>
                            <div
                                class="mt-6 flex flex-col gap-3 sm:flex-row lg:mt-0 lg:shrink-0"
                            >
                                <ButtonSecondary
                                    class="focus:ring-offset-indigo-900"
                                    type="button"
                                    @click="rejectAll"
                                >
                                    {{
                                        $t(
                                            'components.common.cookieBanner.rejectAll',
                                        )
                                    }}
                                </ButtonSecondary>
                                <ButtonSecondary
                                    class="focus:ring-offset-indigo-900"
                                    type="button"
                                    @click="openSettings"
                                >
                                    {{
                                        $t(
                                            'components.common.cookieBanner.customize',
                                        )
                                    }}
                                </ButtonSecondary>
                                <ButtonPrimary
                                    class="focus:ring-offset-indigo-900"
                                    type="button"
                                    @click="acceptAll"
                                >
                                    {{
                                        $t(
                                            'components.common.cookieBanner.acceptAll',
                                        )
                                    }}
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
