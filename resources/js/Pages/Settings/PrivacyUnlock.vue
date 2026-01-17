<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Props {
    message: string;
}

defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

const { countryRoute } = useCountryRoute();
const form = useForm({
    password: '',
});

function unlock() {
    form.post(countryRoute('privacy.unlock'), {
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
    <Head :title="$t('privacy.unlock.title')" />

    <HeaderPage :title="$t('privacy.unlock.title')" />

    <ContainerPage spacing>
        <div
            class="mx-auto max-w-md overflow-hidden rounded-lg border border-indigo-200 bg-white shadow-sm"
        >
            <div class="p-6">
                <div class="mb-6 flex justify-center">
                    <div
                        class="flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100"
                    >
                        <DynamicIcon
                            name="lock-open"
                            class="h-8 w-8 text-indigo-600"
                        />
                    </div>
                </div>

                <p class="mb-6 text-center text-indigo-600">
                    {{ message }}
                </p>

                <form @submit.prevent="unlock">
                    <FormInput
                        id="unlock-password"
                        v-model="form.password"
                        :label="$t('auth.login.password')"
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
                            {{ $t('privacy.unlock.submit') }}
                        </ButtonPrimary>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <Link
                        :href="countryRoute('privacy.recovery')"
                        class="text-sm text-indigo-600 hover:underline"
                    >
                        {{ $t('privacy.unlock.forgotPassword') }}
                    </Link>
                </div>
            </div>
        </div>
    </ContainerPage>
</template>
