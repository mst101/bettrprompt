<script setup lang="ts">
import ButtonDanger from '@/Components/Base/Button/ButtonDanger.vue';
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import Modal from '@/Components/Base/Modal/Modal.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useLocaleRoute } from '@/Composables/useLocaleRoute';
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

const { localeRoute } = useLocaleRoute();
const showDisableModal = ref(false);
const isDisabling = ref(false);

const disableForm = useForm({
    password: '',
    confirm: false,
});

function beginSetup() {
    router.post(localeRoute('privacy.begin-setup'));
}

function disablePrivacy() {
    isDisabling.value = true;
    disableForm.post(localeRoute('privacy.disable'), {
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
    <Head title="Privacy Settings" />

    <HeaderPage title="Privacy Encryption">
        <template #actions>
            <Link
                :href="localeRoute('profile.edit')"
                class="text-sm text-indigo-600 hover:underline"
            >
                Back to Profile
            </Link>
        </template>
    </HeaderPage>

    <ContainerPage spacing>
        <!-- Pro Required Notice (Free tier) -->
        <div
            v-if="!subscription.isPro"
            class="overflow-hidden rounded-lg border border-amber-200 bg-amber-50 shadow-sm"
        >
            <div class="p-6">
                <h2 class="mb-2 text-lg font-semibold text-amber-900">
                    Pro Feature
                </h2>
                <p class="mb-4 text-amber-700">
                    Privacy encryption is available exclusively for Pro
                    subscribers. Your prompt data and personal information will
                    be encrypted at rest, ensuring only you can access your
                    data.
                </p>
                <Link
                    :href="localeRoute('pricing')"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700"
                >
                    Upgrade to Pro
                </Link>
            </div>
        </div>

        <!-- Privacy Status (Pro tier) -->
        <div
            v-else
            class="overflow-hidden rounded-lg border border-indigo-200 bg-white shadow-sm"
        >
            <div class="p-6">
                <h2 class="mb-4 text-lg font-semibold text-indigo-900">
                    Encryption Status
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
                                Encryption Enabled
                            </div>
                            <div class="text-sm text-green-600">
                                Enabled on {{ formatDate(privacy.setupAt) }}
                            </div>
                        </div>
                    </div>

                    <p class="text-indigo-600">
                        Your prompt data is encrypted at rest. Only you can
                        access your data by entering your password.
                    </p>

                    <div class="border-t border-indigo-100 pt-4">
                        <ButtonDanger
                            data-testid="disable-privacy-button"
                            @click="showDisableModal = true"
                        >
                            Disable Encryption
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
                                Encryption Not Enabled
                            </div>
                            <div class="text-sm text-indigo-500">
                                Your data is stored without end-to-end
                                encryption
                            </div>
                        </div>
                    </div>

                    <p class="text-indigo-600">
                        Enable privacy encryption to protect your prompt data at
                        rest. Once enabled, your data will be encrypted with a
                        key derived from your password.
                    </p>

                    <ButtonPrimary
                        data-testid="enable-privacy-button"
                        @click="beginSetup"
                    >
                        Enable Encryption
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
                    How Privacy Encryption Works
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
                                >Password-Protected Key:</strong
                            >
                            A unique encryption key is generated and protected
                            by your account password.
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
                                >Recovery Phrase:</strong
                            >
                            You'll receive a 12-word recovery phrase that can
                            restore access if you forget your password.
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
                                >Encrypted At Rest:</strong
                            >
                            All your prompt data is encrypted before being
                            stored. Without your password, the data is
                            unreadable.
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
                    Disable Privacy Encryption?
                </h3>
                <p class="mb-4 text-indigo-600">
                    This will decrypt all your stored data. Your prompts will no
                    longer be encrypted at rest.
                </p>

                <form @submit.prevent="disablePrivacy">
                    <FormInput
                        id="disable-password"
                        v-model="disableForm.password"
                        label="Enter your password to confirm"
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
                            I understand my data will no longer be encrypted
                        </span>
                    </label>

                    <div class="mt-6 flex gap-4">
                        <ButtonSecondary
                            type="button"
                            class="flex-1"
                            @click="showDisableModal = false"
                        >
                            Cancel
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
                                    ? 'Disabling...'
                                    : 'Disable Encryption'
                            }}
                        </ButtonDanger>
                    </div>
                </form>
            </div>
        </Modal>
    </ContainerPage>
</template>
