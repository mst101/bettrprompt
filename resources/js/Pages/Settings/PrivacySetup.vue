<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    recoveryPhrase: string;
    step: 'show_phrase' | 'confirm_phrase';
}

const props = defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

const words = computed(() => props.recoveryPhrase.split(' '));
const currentStep = ref<'show_phrase' | 'confirm_phrase'>(props.step);
const hasCopied = ref(false);
const hasConfirmedWritten = ref(false);

// Generate 3 random indices for confirmation
const confirmationIndices = ref<number[]>([]);
function generateConfirmationIndices() {
    const indices: number[] = [];
    while (indices.length < 3) {
        const idx = Math.floor(Math.random() * 12);
        if (!indices.includes(idx)) {
            indices.push(idx);
        }
    }
    confirmationIndices.value = indices.sort((a, b) => a - b);
}

const confirmForm = useForm({
    confirmation_words: {} as Record<number, string>,
    password: '',
});

function copyPhrase() {
    navigator.clipboard.writeText(props.recoveryPhrase);
    hasCopied.value = true;
    setTimeout(() => {
        hasCopied.value = false;
    }, 2000);
}

function proceedToConfirmation() {
    generateConfirmationIndices();
    currentStep.value = 'confirm_phrase';
}

function goBack() {
    currentStep.value = 'show_phrase';
    confirmForm.reset();
}

function confirmSetup() {
    // Build confirmation_words array from object
    const wordsArray: string[] = [];
    confirmationIndices.value.forEach((idx) => {
        wordsArray[idx] = confirmForm.confirmation_words[idx] || '';
    });

    confirmForm
        .transform(() => ({
            confirmation_words: confirmForm.confirmation_words,
            password: confirmForm.password,
        }))
        .post(route('privacy.confirm-setup'));
}
</script>

<template>
    <Head title="Enable Privacy Encryption" />

    <HeaderPage title="Enable Privacy Encryption" />

    <ContainerPage spacing>
        <!-- Step 1: Show Recovery Phrase -->
        <div
            v-if="currentStep === 'show_phrase'"
            class="overflow-hidden rounded-lg border border-indigo-200 bg-white shadow-sm"
        >
            <div class="p-6">
                <div
                    class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4"
                >
                    <h3 class="mb-2 font-semibold text-amber-900">
                        Important: Save Your Recovery Phrase
                    </h3>
                    <p class="text-sm text-amber-700">
                        Write down these 12 words in order. This is the
                        <strong>only way</strong> to recover your data if you
                        forget your password. Store it securely and never share
                        it.
                    </p>
                </div>

                <h2 class="mb-4 text-lg font-semibold text-indigo-900">
                    Your Recovery Phrase
                </h2>

                <div
                    class="mb-6 grid grid-cols-3 gap-3 rounded-lg bg-indigo-50 p-4"
                >
                    <div
                        v-for="(word, index) in words"
                        :key="index"
                        class="flex items-center gap-2 rounded bg-white px-3 py-2 shadow-sm"
                    >
                        <span
                            class="text-sm font-medium text-indigo-400"
                            data-testid="word-number"
                        >
                            {{ index + 1 }}.
                        </span>
                        <span
                            class="font-mono text-indigo-900"
                            :data-testid="`recovery-word-${index}`"
                        >
                            {{ word }}
                        </span>
                    </div>
                </div>

                <div class="mb-6 flex gap-3">
                    <ButtonSecondary
                        data-testid="copy-phrase-button"
                        @click="copyPhrase"
                    >
                        {{ hasCopied ? 'Copied!' : 'Copy to Clipboard' }}
                    </ButtonSecondary>
                </div>

                <label class="mb-6 flex items-center gap-2">
                    <input
                        v-model="hasConfirmedWritten"
                        type="checkbox"
                        class="h-4 w-4 rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                        data-testid="confirm-written-checkbox"
                    />
                    <span class="text-sm text-indigo-600">
                        I have written down my recovery phrase and stored it
                        securely
                    </span>
                </label>

                <ButtonPrimary
                    :disabled="!hasConfirmedWritten"
                    data-testid="continue-button"
                    @click="proceedToConfirmation"
                >
                    Continue
                </ButtonPrimary>
            </div>
        </div>

        <!-- Step 2: Confirm Recovery Phrase -->
        <div
            v-else
            class="overflow-hidden rounded-lg border border-indigo-200 bg-white shadow-sm"
        >
            <div class="p-6">
                <h2 class="mb-4 text-lg font-semibold text-indigo-900">
                    Confirm Your Recovery Phrase
                </h2>
                <p class="mb-6 text-indigo-600">
                    Enter the words at the specified positions to confirm you've
                    saved your recovery phrase.
                </p>

                <form @submit.prevent="confirmSetup">
                    <div class="mb-6 space-y-4">
                        <FormInput
                            v-for="idx in confirmationIndices"
                            :id="`word-${idx}`"
                            :key="idx"
                            v-model="confirmForm.confirmation_words[idx]"
                            :label="`Word #${idx + 1}`"
                            :error="confirmForm.errors.confirmation_words"
                            :data-testid="`confirm-word-${idx}`"
                            required
                            autocomplete="off"
                        />
                    </div>

                    <FormInput
                        id="setup-password"
                        v-model="confirmForm.password"
                        label="Enter your account password"
                        type="password"
                        :error="confirmForm.errors.password"
                        data-testid="setup-password"
                        required
                        autocomplete="current-password"
                    />

                    <div class="mt-6 flex gap-4">
                        <ButtonSecondary
                            type="button"
                            data-testid="back-button"
                            @click="goBack"
                        >
                            Back
                        </ButtonSecondary>
                        <ButtonPrimary
                            type="submit"
                            :disabled="confirmForm.processing"
                            :loading="confirmForm.processing"
                            data-testid="confirm-setup-button"
                        >
                            Enable Encryption
                        </ButtonPrimary>
                    </div>
                </form>
            </div>
        </div>
    </ContainerPage>
</template>
