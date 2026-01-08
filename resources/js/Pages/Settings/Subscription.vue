<script setup lang="ts">
import ButtonDanger from '@/Components/Base/Button/ButtonDanger.vue';
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import Modal from '@/Components/Base/Modal/Modal.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useLocaleRoute } from '@/Composables/useLocaleRoute';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Invoice, SubscriptionStatus } from '@/Types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    subscription: SubscriptionStatus;
    invoices: Invoice[];
}

defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

const { localeRoute } = useLocaleRoute();
const showCancelModal = ref(false);
const isCancelling = ref(false);

function openBillingPortal() {
    router.visit(localeRoute('billing.portal'));
}

function cancelSubscription() {
    isCancelling.value = true;
    router.post(
        localeRoute('subscription.cancel'),
        {},
        {
            onFinish: () => {
                isCancelling.value = false;
                showCancelModal.value = false;
            },
        },
    );
}

function resumeSubscription() {
    router.post(localeRoute('subscription.resume'));
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}
</script>

<template>
    <Head :title="$t('subscription.pageTitle')" />

    <HeaderPage :title="$t('subscription.heading')">
        <template #actions>
            <Link
                :href="localeRoute('profile.edit')"
                class="text-sm text-indigo-600 hover:underline"
            >
                {{ $t('subscription.backToProfile') }}
            </Link>
        </template>
    </HeaderPage>

    <ContainerPage spacing>
        <!-- Current Plan -->
        <div
            class="overflow-hidden rounded-lg border border-indigo-200 bg-white shadow-sm"
        >
            <div class="p-6">
                <h2 class="mb-4 text-lg font-semibold text-indigo-900">
                    {{ $t('subscription.currentPlan') }}
                </h2>

                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-indigo-900">
                            {{
                                subscription.isPro
                                    ? $t('pricing.pro.name')
                                    : $t('pricing.free.name')
                            }}
                        </div>
                        <div v-if="subscription.isPro" class="text-indigo-600">
                            <span
                                v-if="subscription.onGracePeriod"
                                class="text-amber-600"
                            >
                                {{
                                    $t('subscription.status.cancelling', {
                                        date: formatDate(
                                            subscription.subscriptionEndsAt,
                                        ),
                                    })
                                }}
                            </span>
                            <span v-else>{{
                                $t('subscription.status.active')
                            }}</span>
                        </div>
                        <div v-else class="text-indigo-600">
                            {{
                                $t('subscription.status.promptsRemaining', {
                                    count: subscription.promptsRemaining,
                                    total: subscription.promptLimit,
                                })
                            }}
                        </div>
                    </div>

                    <div v-if="!subscription.isPro">
                        <Link
                            :href="localeRoute('pricing')"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700"
                        >
                            {{ $t('subscription.actions.upgrade') }}
                        </Link>
                    </div>
                </div>

                <!-- Usage Bar (Free tier only) -->
                <div v-if="!subscription.isPro" class="mt-4">
                    <div class="h-2 overflow-hidden rounded-full bg-indigo-100">
                        <div
                            class="h-full rounded-full transition-all"
                            :class="
                                subscription.promptsUsed >= 8
                                    ? 'bg-amber-500'
                                    : 'bg-indigo-500'
                            "
                            :style="{
                                width:
                                    (subscription.promptsUsed /
                                        subscription.promptLimit) *
                                        100 +
                                    '%',
                            }"
                        />
                    </div>
                    <div class="mt-1 text-sm text-indigo-500">
                        {{
                            $t('subscription.status.promptsUsed', {
                                used: subscription.promptsUsed,
                                total: subscription.promptLimit,
                            })
                        }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Manage Subscription (Pro only) -->
        <div
            v-if="subscription.isPro"
            class="overflow-hidden rounded-lg border border-indigo-200 bg-white shadow-sm"
        >
            <div class="p-6">
                <h2 class="mb-4 text-lg font-semibold text-indigo-900">
                    {{ $t('subscription.manage') }}
                </h2>

                <div class="space-y-4">
                    <ButtonSecondary @click="openBillingPortal">
                        {{ $t('subscription.updatePayment') }}
                    </ButtonSecondary>

                    <ButtonPrimary
                        v-if="subscription.onGracePeriod"
                        @click="resumeSubscription"
                    >
                        {{ $t('subscription.resume') }}
                    </ButtonPrimary>

                    <ButtonDanger
                        v-else
                        data-testid="cancel-subscription-button"
                        @click="showCancelModal = true"
                    >
                        {{ $t('subscription.cancel') }}
                    </ButtonDanger>
                </div>
            </div>
        </div>

        <!-- Invoices -->
        <div
            v-if="invoices.length > 0"
            class="overflow-hidden rounded-lg border border-indigo-200 bg-white shadow-sm"
        >
            <div class="p-6">
                <h2 class="mb-4 text-lg font-semibold text-indigo-900">
                    {{ $t('subscription.billing.title') }}
                </h2>

                <div class="divide-y divide-indigo-100">
                    <div
                        v-for="invoice in invoices"
                        :key="invoice.id"
                        class="flex items-center justify-between py-3"
                    >
                        <div>
                            <div class="font-medium text-indigo-900">
                                {{ invoice.date }}
                            </div>
                            <div class="text-indigo-500">
                                {{ invoice.total }}
                            </div>
                        </div>
                        <a
                            :href="invoice.url"
                            target="_blank"
                            class="text-indigo-600 hover:underline"
                        >
                            {{ $t('subscription.billing.download') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Modal -->
        <Modal
            :show="showCancelModal"
            max-width="md"
            @close="showCancelModal = false"
        >
            <div class="p-6">
                <h3 class="mb-4 text-lg font-semibold text-indigo-900">
                    {{ $t('subscription.cancelConfirm.title') }}
                </h3>
                <p class="mb-6 text-indigo-600">
                    {{ $t('subscription.cancelConfirm.description') }}
                </p>
                <div class="flex gap-4">
                    <ButtonSecondary
                        class="flex-1"
                        @click="showCancelModal = false"
                    >
                        {{ $t('subscription.cancelConfirm.keep') }}
                    </ButtonSecondary>
                    <ButtonDanger
                        class="flex-1"
                        data-testid="confirm-cancel-button"
                        :disabled="isCancelling"
                        @click="cancelSubscription"
                    >
                        {{
                            isCancelling
                                ? $t('subscription.cancelConfirm.processing')
                                : $t('subscription.cancelConfirm.confirm')
                        }}
                    </ButtonDanger>
                </div>
            </div>
        </Modal>
    </ContainerPage>
</template>
