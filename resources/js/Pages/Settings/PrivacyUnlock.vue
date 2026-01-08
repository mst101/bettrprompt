<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Props {
    message: string;
}

defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

const form = useForm({
    password: '',
});

function unlock() {
    form.post(route('privacy.unlock'), {
        onSuccess: () => {
            // Redirect will happen automatically via intended URL
        },
        onError: () => {
            form.reset('password');
        },
    });
}
</script>

<template>
    <Head title="Unlock Privacy" />

    <HeaderPage title="Unlock Your Data" />

    <ContainerPage spacing>
        <div
            class="mx-auto max-w-md overflow-hidden rounded-lg border border-indigo-200 bg-white shadow-sm"
        >
            <div class="p-6">
                <div class="mb-6 flex justify-center">
                    <div
                        class="flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100"
                    >
                        <svg
                            class="h-8 w-8 text-indigo-600"
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
                </div>

                <p class="mb-6 text-center text-indigo-600">
                    {{ message }}
                </p>

                <form @submit.prevent="unlock">
                    <FormInput
                        id="unlock-password"
                        v-model="form.password"
                        label="Password"
                        type="password"
                        :error="form.errors.password"
                        data-testid="unlock-password"
                        required
                        autofocus
                        autocomplete="current-password"
                    />

                    <div class="mt-6">
                        <ButtonPrimary
                            type="submit"
                            class="w-full justify-center"
                            :disabled="form.processing"
                            :loading="form.processing"
                            data-testid="unlock-button"
                        >
                            Unlock
                        </ButtonPrimary>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <Link
                        :href="route('privacy.recovery')"
                        class="text-sm text-indigo-600 hover:underline"
                    >
                        Forgot password? Use recovery phrase
                    </Link>
                </div>
            </div>
        </div>
    </ContainerPage>
</template>
