<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
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
const isAuthenticated = computed(() => !!page.props.auth?.user);
const subscription = computed(() => page.props.subscription);

const selectedPlan = ref<'monthly' | 'yearly'>('yearly');
const isLoading = ref(false);

function subscribe() {
    if (!isAuthenticated.value) {
        // Redirect to home with register modal
        router.visit('/?modal=register');
        return;
    }

    isLoading.value = true;
    router.post(
        route('subscription.checkout'),
        { plan: selectedPlan.value },
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
        router.visit(route('prompt-builder.index'));
    }
}
</script>

<template>
    <Head title="Pricing" />

    <HeaderPage title="Simple, transparent pricing">
        <template #actions>
            <p class="mt-2 text-indigo-600">
                Start free, upgrade when you need more
            </p>
        </template>
    </HeaderPage>

    <ContainerPage>
        <div class="mx-auto max-w-4xl">
            <div class="grid gap-8 md:grid-cols-2">
                <!-- Free Tier -->
                <div
                    class="rounded-2xl border border-indigo-200 bg-white p-8 shadow-sm"
                >
                    <h2 class="mb-2 text-2xl font-bold text-indigo-900">
                        Free
                    </h2>
                    <div class="mb-6 text-4xl font-bold text-indigo-900">
                        &pound;0
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
                            Privacy encryption
                        </li>
                    </ul>

                    <ButtonSecondary
                        class="w-full"
                        data-testid="get-started-button"
                        @click="getStarted"
                    >
                        Get Started
                    </ButtonSecondary>
                </div>

                <!-- Pro Tier -->
                <div
                    class="relative rounded-2xl border-2 border-indigo-500 bg-white p-8 shadow-md"
                >
                    <div
                        class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-indigo-500 px-4 py-1 text-sm font-medium text-white"
                    >
                        Most Popular
                    </div>

                    <h2 class="mb-2 text-2xl font-bold text-indigo-900">Pro</h2>

                    <!-- Plan Toggle -->
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
                            Monthly
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
                            Annual
                        </button>
                    </div>

                    <div class="mb-6">
                        <div class="text-4xl font-bold text-indigo-900">
                            &pound;{{ selectedPlan === 'yearly' ? '99' : '12' }}
                            <span class="text-lg font-normal text-indigo-500">
                                /{{
                                    selectedPlan === 'yearly' ? 'year' : 'month'
                                }}
                            </span>
                        </div>
                        <div
                            v-if="selectedPlan === 'yearly'"
                            class="mt-1 text-sm text-green-600"
                        >
                            &pound;8.25/month - Save 18%
                        </div>
                    </div>

                    <ul class="mb-8 space-y-3">
                        <li
                            v-for="feature in features.pro"
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
                        :disabled="isLoading || subscription?.isPro"
                        :loading="isLoading"
                        @click="subscribe"
                    >
                        <span v-if="subscription?.isPro">Current Plan</span>
                        <span v-else-if="isLoading">Processing...</span>
                        <span v-else>Start Pro</span>
                    </ButtonPrimary>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="mt-16">
                <h2 class="mb-8 text-center text-2xl font-bold text-indigo-900">
                    Frequently Asked Questions
                </h2>

                <div class="space-y-6">
                    <div class="rounded-lg bg-indigo-50 p-6">
                        <h3 class="mb-2 font-semibold text-indigo-900">
                            What happens when I reach my free limit?
                        </h3>
                        <p class="text-indigo-700">
                            Free accounts are limited to 10 prompts per month.
                            Your limit resets on the first of each month. You
                            can upgrade to Pro at any time for unlimited
                            prompts.
                        </p>
                    </div>

                    <div class="rounded-lg bg-indigo-50 p-6">
                        <h3 class="mb-2 font-semibold text-indigo-900">
                            What is privacy encryption?
                        </h3>
                        <p class="text-indigo-700">
                            Pro users get their prompt data encrypted at rest.
                            This means your prompts and responses are protected
                            with encryption keys that only you control, ensuring
                            maximum privacy for sensitive tasks.
                        </p>
                    </div>

                    <div class="rounded-lg bg-indigo-50 p-6">
                        <h3 class="mb-2 font-semibold text-indigo-900">
                            Can I cancel my subscription?
                        </h3>
                        <p class="text-indigo-700">
                            Yes, you can cancel anytime. You'll retain Pro
                            access until the end of your current billing period,
                            then you'll be moved to the Free plan.
                        </p>
                    </div>

                    <div class="rounded-lg bg-indigo-50 p-6">
                        <h3 class="mb-2 font-semibold text-indigo-900">
                            What payment methods do you accept?
                        </h3>
                        <p class="text-indigo-700">
                            We accept all major credit and debit cards, as well
                            as Apple Pay and Google Pay through our secure
                            payment provider, Stripe.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </ContainerPage>
</template>
