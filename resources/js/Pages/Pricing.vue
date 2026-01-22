<script setup lang="ts">
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import PricingTierCard from '@/Components/Pricing/PricingTierCard.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AppLayout from '@/Layouts/AppLayout.vue';
import { analyticsService } from '@/services/analytics';
import type { PricingPlans, SubscriptionStatus } from '@/Types';
import { getCsrfToken } from '@/Utils/cookies';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, inject, ref } from 'vue';

interface Props {
    plans: PricingPlans;
    featureKeys: {
        free: string[];
        starter: string[];
        pro: string[];
        premium: string[];
    };
    currency: string;
    currencySymbol: string;
}

const props = defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

const page = usePage();
const { countryRoute } = useCountryRoute();
const isAuthenticated = computed(() => !!page.props.auth?.user);
const subscription = computed(
    () => page.props.subscription as SubscriptionStatus | undefined,
);

// Inject modal controls from AppLayout
const openRegisterModal = inject<(() => void) | undefined>(
    'openRegisterModal',
    undefined,
);

// Computed properties to get prices from database
const starterPrice = computed(() => {
    const key = `starter_${selectedPlan.value}`;
    const price = props.plans[key]?.price ?? 0;
    // Remove decimals for yearly prices
    return selectedPlan.value === 'yearly' ? Math.round(price) : price;
});

const starterMonthlyEquivalent = computed(() => {
    if (selectedPlan.value === 'yearly') {
        return (starterPrice.value / 12).toFixed(2);
    }
    return null;
});

const proPrice = computed(() => {
    const key = `pro_${selectedPlan.value}`;
    const price = props.plans[key]?.price ?? 0;
    // Remove decimals for yearly prices
    return selectedPlan.value === 'yearly' ? Math.round(price) : price;
});

const proMonthlyEquivalent = computed(() => {
    if (selectedPlan.value === 'yearly') {
        return (proPrice.value / 12).toFixed(2);
    }
    return null;
});

const premiumPrice = computed(() => {
    const key = `premium_${selectedPlan.value}`;
    const price = props.plans[key]?.price ?? 0;
    // Remove decimals for yearly prices
    return selectedPlan.value === 'yearly' ? Math.round(price) : price;
});

const premiumMonthlyEquivalent = computed(() => {
    if (selectedPlan.value === 'yearly') {
        return (premiumPrice.value / 12).toFixed(2);
    }
    return null;
});

const selectedPlan = ref<'monthly' | 'yearly'>('yearly');
const isLoading = ref(false);

async function subscribe(tier: 'starter' | 'pro' | 'premium') {
    // Track subscription started
    analyticsService.track({
        name: 'subscription_started',
        properties: {
            tier,
            interval: selectedPlan.value,
            currency: props.currency,
            source: 'pricing_page',
        },
    });

    if (!isAuthenticated.value) {
        // Open register modal for unauthenticated users
        if (openRegisterModal) {
            openRegisterModal();
        }
        return;
    }

    isLoading.value = true;
    try {
        const csrfToken = getCsrfToken();

        if (!csrfToken) {
            throw new Error('CSRF token not found');
        }

        const response = await fetch(countryRoute('subscription.checkout'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                tier,
                interval: selectedPlan.value,
            }),
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Checkout failed');
        }

        const data = await response.json();
        // Redirect to Stripe checkout
        window.location.href = data.url;
    } catch (error) {
        console.error('Checkout error:', error);
        isLoading.value = false;
        alert('Failed to create checkout session. Please try again.');
    }
}

function getStarted() {
    // Navigate to prompt-builder for both authenticated and unauthenticated users.
    // Unauthenticated users can use the free tier immediately
    router.visit(countryRoute('prompt-builder.index'));
}
</script>

<template>
    <Head :title="$t('pricing.pageTitle')" />

    <HeaderPage :title="$t('pricing.title')">
        <template #subtitle>
            <p class="mt-2 text-indigo-600">
                {{ $t('pricing.tagline') }}
            </p>
        </template>
    </HeaderPage>

    <ContainerPage>
        <div class="mx-auto max-w-7xl">
            <!-- Billing Period Toggle -->
            <div class="mb-8 flex justify-center gap-2">
                <button
                    type="button"
                    data-testid="monthly-toggle"
                    :class="[
                        'rounded-lg px-6 py-2 font-medium transition',
                        selectedPlan === 'monthly'
                            ? 'bg-indigo-100 text-indigo-700'
                            : 'text-indigo-500 hover:bg-indigo-50',
                    ]"
                    @click="selectedPlan = 'monthly'"
                >
                    {{ $t('pricing.billing.monthly') }}
                </button>
                <button
                    type="button"
                    data-testid="annual-toggle"
                    :class="[
                        'rounded-lg px-6 py-2 font-medium transition',
                        selectedPlan === 'yearly'
                            ? 'bg-indigo-100 text-indigo-700'
                            : 'text-indigo-500 hover:bg-indigo-50',
                    ]"
                    @click="selectedPlan = 'yearly'"
                >
                    {{ $t('pricing.billing.yearly') }}
                </button>
            </div>

            <div class="grid gap-8 md:grid-cols-4">
                <!-- Free Tier -->
                <PricingTierCard
                    tier="free"
                    :price="$t('pricing.free.price')"
                    :monthly-equivalent="null"
                    :features="featureKeys.free"
                    :currency-symbol="currencySymbol"
                    :selected-plan="selectedPlan"
                    is-free
                    @get-started="getStarted"
                />

                <!-- Starter Tier -->
                <PricingTierCard
                    tier="starter"
                    :price="starterPrice"
                    :monthly-equivalent="starterMonthlyEquivalent"
                    :features="featureKeys.starter"
                    :currency-symbol="currencySymbol"
                    :selected-plan="selectedPlan"
                    :is-current-plan="subscription?.isStarter ?? false"
                    :is-loading="isLoading"
                    @subscribe="subscribe('starter')"
                />

                <!-- Pro Tier -->
                <PricingTierCard
                    tier="pro"
                    :price="proPrice"
                    :monthly-equivalent="proMonthlyEquivalent"
                    :features="featureKeys.pro"
                    :currency-symbol="currencySymbol"
                    :selected-plan="selectedPlan"
                    :is-recommended="true"
                    :is-current-plan="subscription?.isPro ?? false"
                    :is-loading="isLoading"
                    @subscribe="subscribe('pro')"
                />

                <!-- Premium Tier -->
                <PricingTierCard
                    tier="premium"
                    :price="premiumPrice"
                    :monthly-equivalent="premiumMonthlyEquivalent"
                    :features="featureKeys.premium"
                    :currency-symbol="currencySymbol"
                    :selected-plan="selectedPlan"
                    :is-current-plan="subscription?.isPremium ?? false"
                    :is-loading="isLoading"
                    @subscribe="subscribe('premium')"
                />
            </div>

            <!-- FAQ Section -->
            <div class="mt-16">
                <h2 class="mb-8 text-center text-2xl font-bold text-indigo-900">
                    {{ $t('pricing.faq.title') }}
                </h2>

                <div class="space-y-6">
                    <div class="rounded-lg bg-indigo-50 p-6">
                        <h3 class="mb-2 font-semibold text-indigo-900">
                            {{ $t('pricing.faq.items.limit.question') }}
                        </h3>
                        <p class="text-indigo-700">
                            {{ $t('pricing.faq.items.limit.answer') }}
                        </p>
                    </div>

                    <div class="rounded-lg bg-indigo-50 p-6">
                        <h3 class="mb-2 font-semibold text-indigo-900">
                            {{ $t('pricing.faq.items.privacy.question') }}
                        </h3>
                        <p class="text-indigo-700">
                            {{ $t('pricing.faq.items.privacy.answer') }}
                        </p>
                    </div>

                    <div class="rounded-lg bg-indigo-50 p-6">
                        <h3 class="mb-2 font-semibold text-indigo-900">
                            {{ $t('pricing.faq.items.cancel.question') }}
                        </h3>
                        <p class="text-indigo-700">
                            {{ $t('pricing.faq.items.cancel.answer') }}
                        </p>
                    </div>

                    <div class="rounded-lg bg-indigo-50 p-6">
                        <h3 class="mb-2 font-semibold text-indigo-900">
                            {{ $t('pricing.faq.items.payment.question') }}
                        </h3>
                        <p class="text-indigo-700">
                            {{ $t('pricing.faq.items.payment.answer') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Encryption Notice -->
            <div class="mt-12 text-center text-sm text-gray-600">
                {{ $t('pricing.encryptionNotice') }}
            </div>
        </div>
    </ContainerPage>
</template>
