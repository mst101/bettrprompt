<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AppLayout from '@/Layouts/AppLayout.vue';
import { analyticsService } from '@/services/analytics';
import { Head, router } from '@inertiajs/vue3';
import { onMounted } from 'vue';

defineProps<Props>();

const { countryRoute } = useCountryRoute();

defineOptions({
    layout: AppLayout,
});

interface Props {
    message: string;
}

// Track checkout cancellation
onMounted(() => {
    analyticsService.track({
        name: 'checkout_cancelled',
        properties: {
            source: 'stripe_checkout',
        },
    });
});

function goToPricing() {
    router.visit(countryRoute('pricing'));
}

function goToPromptBuilder() {
    router.visit(countryRoute('prompt-builder.index'));
}
</script>

<template>
    <Head :title="$t('subscription.cancelled.title')" />

    <ContainerPage>
        <div class="flex min-h-[50vh] items-center justify-center">
            <div class="max-w-md text-center">
                <div
                    class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100"
                >
                    <DynamicIcon
                        name="x-mark"
                        class="h-8 w-8 text-indigo-600"
                    />
                </div>

                <h1 class="mb-4 text-3xl font-bold text-indigo-900">
                    {{ $t('subscription.cancelled.heading') }}
                </h1>

                <p class="mb-8 text-indigo-600">
                    {{
                        $t('subscription.cancelled.description', {
                            message,
                        })
                    }}
                </p>

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <ButtonSecondary @click="goToPromptBuilder">
                        {{ $t('subscription.cancelled.actions.continueFree') }}
                    </ButtonSecondary>
                    <ButtonPrimary
                        data-testid="try-again-button"
                        @click="goToPricing"
                    >
                        {{ $t('subscription.cancelled.actions.viewPricing') }}
                    </ButtonPrimary>
                </div>
            </div>
        </div>
    </ContainerPage>
</template>
