<script setup lang="ts">
import ButtonDanger from '@/Components/Base/Button/ButtonDanger.vue';
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import Modal from '@/Components/Base/Modal/Modal.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { PrivacyStatus, SubscriptionStatus } from '@/Types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    privacy: PrivacyStatus;
    subscription: SubscriptionStatus;
}

defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

const { countryRoute } = useCountryRoute();
const showDisableModal = ref(false);
const isDisabling = ref(false);

const disableForm = useForm({
    password: '',
    confirm: false,
});

function beginSetup() {
    router.post(countryRoute('privacy.begin-setup'));
}

function disablePrivacy() {
    isDisabling.value = true;
    disableForm.post(countryRoute('privacy.disable'), {
        onFinish: () => {
            isDisabling.value = false;
            showDisableModal.value = false;
            disableForm.reset();
        },
    });
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
    <Head :title="$t('privacy.pageTitle')" />

    <HeaderPage :title="$t('privacy.title')">
        <template #actions>
            <Link
                :href="countryRoute('profile.edit')"
                class="text-sm text-indigo-600 hover:underline"
            >
                {{ $t('privacy.backToProfile') }}
            </Link>
        </template>
    </HeaderPage>

    <ContainerPage spacing>
        <!-- Pro/Private Required Notice (Free tier) -->
        <div
            v-if="!subscription.isPaid"
            class="overflow-hidden rounded-lg border border-amber-200 bg-amber-50 shadow-sm"
        >
            <div class="p-6">
                <h2 class="mb-2 text-lg font-semibold text-amber-900">
                    {{ $t('privacy.proRequired.title') }}
                </h2>
                <p class="mb-4 text-amber-700">
                    {{ $t('privacy.proRequired.description') }}
                </p>
                <Link
                    :href="countryRoute('pricing')"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700"
                >
                    {{ $t('subscription.actions.upgrade') }}
                </Link>
            </div>
        </div>

        <!-- Privacy Status (Pro or Private tier) -->
        <div
            v-else
            class="overflow-hidden rounded-lg border border-indigo-200 bg-white shadow-sm"
        >
            <div class="p-6">
                <h2 class="mb-4 text-lg font-semibold text-indigo-900">
                    {{ $t('privacy.status.title') }}
                </h2>

                <div v-if="privacy.enabled" class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100"
                        >
                            <svg
                                class="h-6 w-6 text-green-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-green-900">
                                {{ $t('privacy.status.enabled') }}
                            </div>
                            <div class="text-sm text-green-600">
                                {{
                                    $t('privacy.status.enabledOn', {
                                        date: formatDate(privacy.setupAt),
                                    })
                                }}
                            </div>
                        </div>
                    </div>

                    <p class="text-indigo-600">
                        {{ $t('privacy.status.enabledDescription') }}
                    </p>

                    <div class="border-t border-indigo-100 pt-4">
                        <ButtonDanger
                            data-testid="disable-privacy-button"
                            @click="showDisableModal = true"
                        >
                            {{ $t('privacy.actions.disable') }}
                        </ButtonDanger>
                    </div>
                </div>

                <div v-else class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100"
                        >
                            <svg
                                class="h-6 w-6 text-indigo-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-indigo-900">
                                {{ $t('privacy.status.notEnabled') }}
                            </div>
                            <div class="text-sm text-indigo-500">
                                {{ $t('privacy.status.notEnabledDescription') }}
                            </div>
                        </div>
                    </div>

                    <p class="text-indigo-600">
                        {{ $t('privacy.status.enableDescription') }}
                    </p>

                    <ButtonPrimary
                        data-testid="enable-privacy-button"
                        @click="beginSetup"
                    >
                        {{ $t('privacy.actions.enable') }}
                    </ButtonPrimary>
                </div>
            </div>
        </div>

        <!-- How It Works -->
        <div
            v-if="subscription.isPro"
            class="overflow-hidden rounded-lg border border-indigo-200 bg-white shadow-sm"
        >
            <div class="p-6">
                <h2 class="mb-4 text-lg font-semibold text-indigo-900">
                    {{ $t('privacy.howItWorks.title') }}
                </h2>

                <div class="space-y-4 text-indigo-600">
                    <div class="flex gap-3">
                        <div
                            class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-medium text-indigo-600"
                        >
                            1
                        </div>
                        <p>
                            <strong class="text-indigo-900"
                                >{{
                                    $t('privacy.howItWorks.step1.title')
                                }}:</strong
                            >
                            {{ $t('privacy.howItWorks.step1.description') }}
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <div
                            class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-medium text-indigo-600"
                        >
                            2
                        </div>
                        <p>
                            <strong class="text-indigo-900"
                                >{{
                                    $t('privacy.howItWorks.step2.title')
                                }}:</strong
                            >
                            {{ $t('privacy.howItWorks.step2.description') }}
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <div
                            class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-medium text-indigo-600"
                        >
                            3
                        </div>
                        <p>
                            <strong class="text-indigo-900"
                                >{{
                                    $t('privacy.howItWorks.step3.title')
                                }}:</strong
                            >
                            {{ $t('privacy.howItWorks.step3.description') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disable Modal -->
        <Modal
            :show="showDisableModal"
            max-width="md"
            @close="showDisableModal = false"
        >
            <div class="p-6">
                <h3 class="mb-4 text-lg font-semibold text-indigo-900">
                    {{ $t('privacy.disable.title') }}
                </h3>
                <p class="mb-4 text-indigo-600">
                    {{ $t('privacy.disable.description') }}
                </p>

                <form @submit.prevent="disablePrivacy">
                    <FormInput
                        id="disable-password"
                        v-model="disableForm.password"
                        :label="$t('privacy.disable.passwordLabel')"
                        type="password"
                        :error="disableForm.errors.password"
                        required
                        autocomplete="current-password"
                    />

                    <label class="mt-4 flex items-center gap-2">
                        <input
                            v-model="disableForm.confirm"
                            type="checkbox"
                            class="h-4 w-4 rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span class="text-sm text-indigo-600">
                            {{ $t('privacy.disable.confirmLabel') }}
                        </span>
                    </label>

                    <div class="mt-6 flex gap-4">
                        <ButtonSecondary
                            type="button"
                            class="flex-1"
                            @click="showDisableModal = false"
                        >
                            {{ $t('common.buttons.cancel') }}
                        </ButtonSecondary>
                        <ButtonDanger
                            type="submit"
                            class="flex-1"
                            data-testid="confirm-disable-button"
                            :disabled="
                                isDisabling ||
                                !disableForm.confirm ||
                                !disableForm.password
                            "
                        >
                            {{
                                isDisabling
                                    ? $t('privacy.disable.processing')
                                    : $t('privacy.actions.disable')
                            }}
                        </ButtonDanger>
                    </div>
                </form>
            </div>
        </Modal>
    </ContainerPage>
</template>
