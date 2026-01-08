<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    wordList: string[];
}

defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

// Store individual words for the recovery phrase
const recoveryWords = ref<string[]>(Array(12).fill(''));

const form = useForm({
    recovery_phrase: '',
    new_password: '',
    new_password_confirmation: '',
});

function updateRecoveryPhrase() {
    form.recovery_phrase = recoveryWords.value
        .map((w) => w.trim().toLowerCase())
        .join(' ');
}

function recover() {
    updateRecoveryPhrase();
    form.post(route('privacy.recover'), {
        onError: () => {
            form.reset('new_password', 'new_password_confirmation');
        },
    });
}
</script>

<template>
    <Head title="Recover Privacy Key" />

    <HeaderPage title="Recover Your Data" />

    <ContainerPage spacing>
        <div
            class="mx-auto max-w-2xl overflow-hidden rounded-lg border border-indigo-200 bg-white shadow-sm"
        >
            <div class="p-6">
                <div
                    class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4"
                >
                    <h3 class="mb-2 font-semibold text-amber-900">
                        Account Recovery
                    </h3>
                    <p class="text-sm text-amber-700">
                        Enter your 12-word recovery phrase to regain access to
                        your encrypted data. You will also need to set a new
                        password.
                    </p>
                </div>

                <form @submit.prevent="recover">
                    <h2 class="mb-4 text-lg font-semibold text-indigo-900">
                        Recovery Phrase
                    </h2>

                    <div class="mb-6 grid grid-cols-3 gap-3">
                        <div
                            v-for="(_, index) in 12"
                            :key="index"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="w-6 text-right text-sm font-medium text-indigo-400"
                            >
                                {{ index + 1 }}.
                            </span>
                            <input
                                v-model="recoveryWords[index]"
                                type="text"
                                class="w-full rounded-lg border border-indigo-200 px-3 py-2 font-mono text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :data-testid="`recovery-word-input-${index}`"
                                autocomplete="off"
                                autocapitalize="off"
                                @input="updateRecoveryPhrase"
                            />
                        </div>
                    </div>

                    <div
                        v-if="form.errors.recovery_phrase"
                        class="mb-4 text-sm text-red-600"
                    >
                        {{ form.errors.recovery_phrase }}
                    </div>

                    <h2 class="mb-4 text-lg font-semibold text-indigo-900">
                        Set New Password
                    </h2>

                    <div class="space-y-4">
                        <FormInput
                            id="new-password"
                            v-model="form.new_password"
                            label="New Password"
                            type="password"
                            :error="form.errors.new_password"
                            data-testid="new-password"
                            required
                            autocomplete="new-password"
                        />

                        <FormInput
                            id="new-password-confirmation"
                            v-model="form.new_password_confirmation"
                            label="Confirm New Password"
                            type="password"
                            data-testid="new-password-confirmation"
                            required
                            autocomplete="new-password"
                        />
                    </div>

                    <div class="mt-6">
                        <ButtonPrimary
                            type="submit"
                            :disabled="form.processing"
                            :loading="form.processing"
                            data-testid="recover-button"
                        >
                            Recover Account
                        </ButtonPrimary>
                    </div>
                </form>
            </div>
        </div>
    </ContainerPage>
</template>
