<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';

interface Props {
    variant?: 'fixed' | 'inline';
    visitorHasAccount?: boolean;
}

withDefaults(defineProps<Props>(), {
    variant: 'fixed',
    visitorHasAccount: false,
});

defineEmits<{
    (e: 'register'): void;
    (e: 'login'): void;
}>();
</script>

<template>
    <!-- Fixed bottom banner variant -->
    <div
        v-if="variant === 'fixed'"
        class="fixed right-0 bottom-0 left-0 z-50 bg-indigo-800"
    >
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <div
                class="flex flex-col items-center justify-between gap-4 sm:flex-row"
            >
                <div class="flex items-start gap-3 text-white">
                    <DynamicIcon
                        name="information-circle"
                        class="mt-0.5 h-6 w-6 shrink-0"
                    />
                    <div>
                        <p class="font-semibold">
                            <template v-if="visitorHasAccount">
                                {{
                                    $t(
                                        'components.common.visitorLimitBanner.fixed.titleReturning',
                                    )
                                }}
                            </template>
                            <template v-else>
                                {{
                                    $t(
                                        'components.common.visitorLimitBanner.fixed.titleNew',
                                    )
                                }}
                            </template>
                        </p>
                        <p class="mt-1 text-indigo-100">
                            <template v-if="visitorHasAccount">
                                {{
                                    $t(
                                        'components.common.visitorLimitBanner.fixed.descriptionReturning',
                                    )
                                }}
                            </template>
                            <template v-else>
                                {{
                                    $t(
                                        'components.common.visitorLimitBanner.fixed.descriptionNew',
                                    )
                                }}
                            </template>
                        </p>
                    </div>
                </div>
                <ButtonPrimary
                    class="shrink-0"
                    icon="arrow-right"
                    @click="$emit(visitorHasAccount ? 'login' : 'register')"
                >
                    <template v-if="visitorHasAccount">
                        {{ $t('common.nav.login') }}
                    </template>
                    <template v-else>
                        {{
                            $t(
                                'components.common.visitorLimitBanner.fixed.ctaRegister',
                            )
                        }}
                    </template>
                </ButtonPrimary>
            </div>
        </div>
    </div>

    <!-- Inline card variant -->
    <div
        v-else
        class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-4"
    >
        <div class="flex items-start gap-4">
            <DynamicIcon
                name="information-circle"
                class="mt-0.5 h-6 w-6 shrink-0 text-indigo-600"
            />
            <h3 class="font-semibold text-indigo-900">
                <template v-if="visitorHasAccount">
                    {{
                        $t(
                            'components.common.visitorLimitBanner.inline.titleReturning',
                        )
                    }}
                </template>
                <template v-else>
                    {{
                        $t(
                            'components.common.visitorLimitBanner.inline.titleNew',
                        )
                    }}
                </template>
            </h3>
        </div>
        <div>
            <p class="mt-2 text-indigo-700">
                <template v-if="visitorHasAccount">
                    {{
                        $t(
                            'components.common.visitorLimitBanner.inline.descriptionReturning',
                        )
                    }}
                </template>
                <template v-else>
                    {{
                        $t(
                            'components.common.visitorLimitBanner.inline.descriptionNew',
                        )
                    }}
                </template>
            </p>
            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:justify-end">
                <template v-if="visitorHasAccount">
                    <ButtonPrimary
                        class="w-full sm:w-fit"
                        icon="arrow-right"
                        @click="$emit('login')"
                    >
                        {{ $t('common.nav.login') }}
                    </ButtonPrimary>
                </template>
                <template v-else>
                    <ButtonPrimary
                        class="w-full sm:w-fit"
                        icon="arrow-right"
                        @click="$emit('register')"
                    >
                        {{
                            $t(
                                'components.common.visitorLimitBanner.inline.ctaRegister',
                            )
                        }}
                    </ButtonPrimary>
                    <ButtonPrimary
                        class="w-full sm:w-fit"
                        icon="arrow-right"
                        @click="$emit('login')"
                    >
                        {{ $t('common.nav.login') }}
                    </ButtonPrimary>
                </template>
            </div>
        </div>
    </div>
</template>
