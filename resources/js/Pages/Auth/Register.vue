<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({
    layout: AppLayout,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    passwordConfirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'passwordConfirmation');
        },
    });
};
</script>

<template>
    <Head title="Create an Account" />

    <form @submit.prevent="submit">
        <FormInput
            id="name"
            v-model="form.name"
            label="Name"
            type="text"
            :error="form.errors.name"
            required
            autofocus
            autocomplete="name"
        />

        <FormInput
            id="email"
            v-model="form.email"
            label="Email"
            type="email"
            :error="form.errors.email"
            class="mt-4"
            required
            autocomplete="username"
        />

        <FormInput
            id="password"
            v-model="form.password"
            label="Password"
            type="password"
            :error="form.errors.password"
            class="mt-4"
            required
            autocomplete="new-password"
        />

        <FormInput
            id="password-confirmation"
            v-model="form.passwordConfirmation"
            label="Confirm Password"
            type="password"
            :error="form.errors.passwordConfirmation"
            class="mt-4"
            required
            autocomplete="new-password"
        />

        <div class="mt-4 flex items-center justify-end">
            <Link
                :href="route('login')"
                class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden"
            >
                Already registered?
            </Link>

            <ButtonPrimary
                type="submit"
                class="ms-4"
                :disabled="form.processing"
                :loading="form.processing"
            >
                Register
            </ButtonPrimary>
        </div>
    </form>
</template>
