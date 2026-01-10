<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useLocaleRoute } from '@/Composables/useLocaleRoute';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { PricingFeatures, PricingPlans } from '@/Types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    plans: PricingPlans;
    features: PricingFeatures;
}

defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

const page = usePage();
const { localeRoute } = useLocaleRoute();
const isAuthenticated = computed(() => !!page.props.auth?.user);
const subscription = computed(() => page.props.subscription);

const selectedPlan = ref<'monthly' | 'yearly'>('yearly');
const selectedTier = ref<'pro' | 'private'>('private');
const isLoading = ref(false);

function subscribe(tier: 'pro' | 'private') {
    if (!isAuthenticated.value) {
        // Redirect to home with register modal
        router.visit('/?modal=register');
        return;
    }

    isLoading.value = true;
    router.post(
        route('subscription.checkout'),
        { tier, interval: selectedPlan.value },
        {
            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
}

function getStarted() {
    if (!isAuthenticated.value) {
        router.visit('/?modal=register');
    } else {
        router.visit(localeRoute('prompt-builder.index'));
    }
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
        <div class="mx-auto max-w-6xl">
            <!-- Pricing Tier Selector -->
            <div class="mb-8 flex justify-center gap-2">
                <button
                    type="button"
                    data-testid="pro-tier-tab"
                    :class="[
                        'rounded-lg px-6 py-2 font-medium transition',
                        selectedTier === 'pro'
                            ? 'bg-indigo-100 text-indigo-700'
                            : 'text-indigo-500 hover:bg-indigo-50',
                    ]"
                    @click="selectedTier = 'pro'"
                >
                    {{ $t('pricing.pro.name') }}
                </button>
                <button
                    type="button"
                    data-testid="private-tier-tab"
                    :class="[
                        'rounded-lg px-6 py-2 font-medium transition',
                        selectedTier === 'private'
                            ? 'bg-indigo-100 text-indigo-700'
                            : 'text-indigo-500 hover:bg-indigo-50',
                    ]"
                    @click="selectedTier = 'private'"
                >
                    {{ $t('pricing.private.name') }}
                </button>
            </div>

            <div class="grid gap-8 md:grid-cols-2">
                <!-- Free Tier -->
                <div
                    class="rounded-2xl border border-indigo-200 bg-white p-8 shadow-sm"
                >
                    <h2 class="mb-2 text-2xl font-bold text-indigo-900">
                        {{ $t('pricing.free.name') }}
                    </h2>
                    <div class="mb-6 text-4xl font-bold text-indigo-900">
                        {{ $t('pricing.currency') }}
                        {{ $t('pricing.free.price') }}
                    </div>

                    <ul class="mb-8 space-y-3">
                        <li
                            v-for="feature in features.free"
                            :key="feature"
                            class="flex items-center gap-2 text-indigo-700"
                        >
                            <DynamicIcon
                                name="check"
                                class="h-5 w-5 text-green-500"
                            />
                            {{ feature }}
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

                <!-- Pro / Private Tier (Tabs) -->
                <div
                    class="relative rounded-2xl border-2 border-indigo-500 bg-white p-8 shadow-md"
                >
                    <div
                        class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-indigo-500 px-4 py-1 text-sm font-medium text-white"
                    >
                        {{ $t('pricing.popularBadge') }}
                    </div>

                    <h2 class="mb-2 text-2xl font-bold text-indigo-900">
                        {{
                            selectedTier === 'pro'
                                ? $t('pricing.pro.name')
                                : $t('pricing.private.name')
                        }}
                    </h2>

                    <!-- Plan Toggle (Monthly/Yearly) -->
                    <div class="mb-4 flex gap-2">
                        <button
                            type="button"
                            data-testid="monthly-toggle"
                            :class="[
                                'rounded-lg px-4 py-2 text-sm font-medium transition',
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
                                'rounded-lg px-4 py-2 text-sm font-medium transition',
                                selectedPlan === 'yearly'
                                    ? 'bg-indigo-100 text-indigo-700'
                                    : 'text-indigo-500 hover:bg-indigo-50',
                            ]"
                            @click="selectedPlan = 'yearly'"
                        >
                            {{ $t('pricing.billing.yearly') }}
                        </button>
                    </div>

                    <div class="mb-6">
                        <div class="text-4xl font-bold text-indigo-900">
                            {{ $t('pricing.currency') }}
                            {{
                                selectedTier === 'pro'
                                    ? selectedPlan === 'yearly'
                                        ? $t('pricing.pro.priceYearly')
                                        : $t('pricing.pro.priceMonthly')
                                    : selectedPlan === 'yearly'
                                      ? $t('pricing.private.priceYearly')
                                      : $t('pricing.private.priceMonthly')
                            }}
                            <span class="text-lg font-normal text-indigo-500">
                                /{{
                                    selectedPlan === 'yearly'
                                        ? $t('pricing.period.year')
                                        : $t('pricing.period.month')
                                }}
                            </span>
                        </div>
                        <div
                            v-if="selectedPlan === 'yearly'"
                            class="mt-1 text-sm text-green-600"
                        >
                            {{
                                selectedTier === 'pro'
                                    ? $t('pricing.pro.yearlySavings', {
                                          amount: `${$t('pricing.currency')} 10`,
                                          period: $t('pricing.period.month'),
                                          percent: 17,
                                      })
                                    : $t('pricing.private.yearlySavings', {
                                          amount: `${$t('pricing.currency')} 16.67`,
                                          period: $t('pricing.period.month'),
                                          percent: 17,
                                      })
                            }}
                        </div>
                    </div>

                    <ul class="mb-8 space-y-3">
                        <li
                            v-for="feature in selectedTier === 'pro'
                                ? features.pro
                                : features.private"
                            :key="feature"
                            class="flex items-center gap-2 text-indigo-700"
                        >
                            <DynamicIcon
                                name="check"
                                class="h-5 w-5 text-green-500"
                            />
                            {{ feature }}
                        </li>
                    </ul>

                    <ButtonPrimary
                        class="w-full"
                        data-testid="subscribe-button"
                        :disabled="
                            isLoading ||
                            (selectedTier === 'pro' && subscription?.isPro) ||
                            (selectedTier === 'private' &&
                                subscription?.isPrivate)
                        "
                        :loading="isLoading"
                        @click="subscribe(selectedTier)"
                    >
                        <span
                            v-if="
                                (selectedTier === 'pro' &&
                                    subscription?.isPro) ||
                                (selectedTier === 'private' &&
                                    subscription?.isPrivate)
                            "
                        >
                            {{ $t('subscription.currentPlan') }}
                        </span>
                        <span v-else-if="isLoading">
                            {{ $t('pricing.actions.processing') }}
                        </span>
                        <span v-else>
                            {{
                                selectedTier === 'pro'
                                    ? $t('pricing.pro.cta')
                                    : $t('pricing.private.cta')
                            }}
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
