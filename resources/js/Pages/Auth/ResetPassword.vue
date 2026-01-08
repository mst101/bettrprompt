<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    email: string;
    token: string;
}>();

defineOptions({
    layout: AppLayout,
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    passwordConfirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'passwordConfirmation');
        },
    });
};
</script>

<template>
    <Head :title="$t('auth.resetPassword.headTitle')" />

    <h1 class="mb-6 text-lg font-bold text-indigo-900 sm:text-2xl">
        {{ $t('auth.resetPassword.title') }}
    </h1>

    <form @submit.prevent="submit">
        <FormInput
            id="email"
            v-model="form.email"
            :label="$t('auth.resetPassword.email')"
            type="email"
            :error="form.errors.email"
            required
            autofocus
            autocomplete="username"
        />

        <FormInput
            id="password"
            v-model="form.password"
            :label="$t('auth.resetPassword.password')"
            type="password"
            :error="form.errors.password"
            class="mt-4"
            required
            autocomplete="new-password"
        />

        <FormInput
            id="password-confirmation"
            v-model="form.passwordConfirmation"
            :label="$t('auth.resetPassword.confirmPassword')"
            type="password"
            :error="form.errors.passwordConfirmation"
            class="mt-4"
            required
            autocomplete="new-password"
        />

        <div class="mt-4 flex items-center justify-end">
            <ButtonPrimary
                type="submit"
                :disabled="form.processing"
                :loading="form.processing"
                icon="check"
            >
                {{ $t('auth.resetPassword.submit') }}
            </ButtonPrimary>
        </div>
    </form>
</template>
