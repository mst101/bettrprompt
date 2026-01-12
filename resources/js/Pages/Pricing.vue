<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { PricingPlans, SubscriptionStatus } from '@/Types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, inject, ref } from 'vue';

interface Props {
    plans: PricingPlans;
    featureKeys: {
        free: string[];
        pro: string[];
        private: string[];
    };
    currency: string;
    currencySymbol: string;
    availableCurrencies: string[];
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
const proPrice = computed(() => {
    const key = `pro_${selectedPlan.value}`;
    const price = props.plans[key]?.price ?? 0;
    // Remove decimals for yearly prices
    return selectedPlan.value === 'yearly' ? Math.round(price) : price;
});

const privatePrice = computed(() => {
    const key = `private_${selectedPlan.value}`;
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

const privateMonthlyEquivalent = computed(() => {
    if (selectedPlan.value === 'yearly') {
        return (privatePrice.value / 12).toFixed(2);
    }
    return null;
});

const selectedPlan = ref<'monthly' | 'yearly'>('yearly');
const isLoading = ref(false);
const isCurrencyUpdating = ref(false);

// Keep selectedCurrency in sync with props.currency (updates after redirect)
const selectedCurrency = computed(() => props.currency);

function updateCurrency(newCurrency: string) {
    router.post(
        countryRoute('currency.select'),
        { currency_code: newCurrency },
        {
            onStart: () => {
                isCurrencyUpdating.value = true;
            },
            onFinish: () => {
                isCurrencyUpdating.value = false;
            },
        },
    );
}

function subscribe(tier: 'pro' | 'private') {
    if (!isAuthenticated.value) {
        // Open register modal for unauthenticated users
        if (openRegisterModal) {
            openRegisterModal();
        }
        return;
    }

    isLoading.value = true;
    router.post(
        countryRoute('subscription.checkout'),
        { tier, interval: selectedPlan.value },
        {
            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
}

function getStarted() {
    // Navigate to prompt-builder for both authenticated and unauthenticated users
    // Unauthenticated users can use the free tier immediately
    router.visit(countryRoute('prompt-builder.index'));
}
</script>

<template>
    <Head :title="$t('pricing.pageTitle')" />

    <HeaderPage :title="$t('pricing.title')">
        <template #actions>
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

            <!-- Currency Switcher -->
            <div class="mb-8 flex justify-center gap-2">
                <button
                    v-for="curr in availableCurrencies"
                    :key="curr"
                    type="button"
                    :data-testid="`currency-${curr.toLowerCase()}`"
                    :disabled="isCurrencyUpdating"
                    :class="[
                        'rounded-lg px-4 py-2 text-sm font-medium transition',
                        selectedCurrency === curr
                            ? 'bg-green-100 text-green-700'
                            : 'text-gray-600 hover:bg-gray-50',
                        isCurrencyUpdating && 'cursor-not-allowed opacity-50',
                    ]"
                    @click="updateCurrency(curr)"
                >
                    {{ curr }}
                </button>
            </div>

            <div class="grid gap-8 md:grid-cols-3">
                <!-- Free Tier -->
                <div
                    class="rounded-2xl border border-indigo-200 bg-white p-8 shadow-sm"
                >
                    <h2 class="mb-2 text-2xl font-bold text-indigo-900">
                        {{ $t('pricing.free.name') }}
                    </h2>
                    <div class="mb-6 text-4xl font-bold text-indigo-900">
                        {{ $t('pricing.currency')
                        }}{{ $t('pricing.free.price') }}
                    </div>

                    <ul class="mb-8 space-y-3">
                        <li
                            v-for="featureKey in featureKeys.free"
                            :key="featureKey"
                            class="flex items-center gap-2 text-indigo-700"
                        >
                            <DynamicIcon
                                name="check"
                                class="h-5 w-5 text-green-500"
                            />
                            {{ $t(featureKey) }}
                        </li>
                        <li class="flex items-center gap-2 text-indigo-400">
                            <DynamicIcon
                                name="x-mark"
                                class="h-5 w-5 text-indigo-300"
                            />
                            {{ $t('pricing.features.privacy') }}
                        </li>
                    </ul>

                    <ButtonSecondary
                        class="w-full"
                        data-testid="get-started-button"
                        @click="getStarted"
                    >
                        {{ $t('pricing.free.cta') }}
                    </ButtonSecondary>
                </div>

                <!-- Pro Tier -->
                <div
                    class="rounded-2xl border border-indigo-200 bg-white p-8 shadow-sm"
                    data-testid="pro-tier-tab"
                >
                    <h2 class="mb-2 text-2xl font-bold text-indigo-900">
                        {{ $t('pricing.pro.name') }}
                    </h2>

                    <div class="mb-6">
                        <div class="text-4xl font-bold text-indigo-900">
                            {{ currencySymbol }}{{ proPrice }}
                            <span class="text-lg font-normal text-indigo-500">
                                /{{
                                    selectedPlan === 'yearly'
                                        ? $t('pricing.period.year')
                                        : $t('pricing.period.month')
                                }}
                            </span>
                        </div>
                        <div
                            v-if="
                                selectedPlan === 'yearly' &&
                                proMonthlyEquivalent
                            "
                            class="mt-1 text-sm text-green-600"
                        >
                            {{
                                $t('pricing.pro.yearlySavings', {
                                    amount: `${currencySymbol}${proMonthlyEquivalent}`,
                                    period: $t('pricing.period.month'),
                                    percent: 17,
                                })
                            }}
                        </div>
                    </div>

                    <ul class="mb-8 space-y-3">
                        <li
                            v-for="featureKey in featureKeys.pro"
                            :key="featureKey"
                            class="flex items-center gap-2 text-indigo-700"
                        >
                            <DynamicIcon
                                name="check"
                                class="h-5 w-5 text-green-500"
                            />
                            {{ $t(featureKey) }}
                        </li>
                        <li class="flex items-center gap-2 text-indigo-400">
                            <DynamicIcon
                                name="x-mark"
                                class="h-5 w-5 text-indigo-300"
                            />
                            {{ $t('pricing.features.privacy') }}
                        </li>
                    </ul>

                    <ButtonPrimary
                        class="w-full"
                        data-testid="subscribe-button"
                        :disabled="isLoading || subscription?.isPro"
                        :loading="isLoading"
                        @click="subscribe('pro')"
                    >
                        <span v-if="subscription?.isPro">
                            {{ $t('messages.subscription.current_plan') }}
                        </span>
                        <span v-else-if="isLoading">
                            {{ $t('pricing.actions.processing') }}
                        </span>
                        <span v-else>
                            {{ $t('pricing.pro.cta') }}
                        </span>
                    </ButtonPrimary>
                </div>

                <!-- Private Tier -->
                <div
                    class="relative rounded-2xl border-2 border-indigo-500 bg-white p-8 shadow-md"
                    data-testid="private-tier-tab"
                >
                    <div
                        class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-indigo-500 px-4 py-1 text-sm font-medium text-white"
                    >
                        {{ $t('pricing.popularBadge') }}
                    </div>

                    <h2 class="mb-2 text-2xl font-bold text-indigo-900">
                        {{ $t('pricing.private.name') }}
                    </h2>

                    <div class="mb-6">
                        <div class="text-4xl font-bold text-indigo-900">
                            {{ currencySymbol }}{{ privatePrice }}
                            <span class="text-lg font-normal text-indigo-500">
                                /{{
                                    selectedPlan === 'yearly'
                                        ? $t('pricing.period.year')
                                        : $t('pricing.period.month')
                                }}
                            </span>
                        </div>
                        <div
                            v-if="
                                selectedPlan === 'yearly' &&
                                privateMonthlyEquivalent
                            "
                            class="mt-1 text-sm text-green-600"
                        >
                            {{
                                $t('pricing.private.yearlySavings', {
                                    amount: `${currencySymbol}${privateMonthlyEquivalent}`,
                                    period: $t('pricing.period.month'),
                                    percent: 17,
                                })
                            }}
                        </div>
                    </div>

                    <ul class="mb-8 space-y-3">
                        <li
                            v-for="featureKey in featureKeys.private"
                            :key="featureKey"
                            class="flex items-center gap-2 text-indigo-700"
                        >
                            <DynamicIcon
                                name="check"
                                class="h-5 w-5 text-green-500"
                            />
                            {{ $t(featureKey) }}
                        </li>
                    </ul>

                    <ButtonPrimary
                        class="w-full"
                        data-testid="subscribe-button"
                        :disabled="isLoading || subscription?.isPrivate"
                        :loading="isLoading"
                        @click="subscribe('private')"
                    >
                        <span v-if="subscription?.isPrivate">
                            {{ $t('messages.subscription.current_plan') }}
                        </span>
                        <span v-else-if="isLoading">
                            {{ $t('pricing.actions.processing') }}
                        </span>
                        <span v-else>
                            {{ $t('pricing.private.cta') }}
                        </span>
                    </ButtonPrimary>
                </div>
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
        </div>
    </ContainerPage>
</template>
