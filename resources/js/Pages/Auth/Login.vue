<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import Checkbox from '@/Components/Base/Checkbox.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

defineOptions({
    layout: AppLayout,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <Head title="Log in" />

    <h1 class="mb-6 text-lg font-bold text-indigo-900 sm:text-2xl">
        Log in to Your Account
    </h1>

    <div
        v-if="status"
        class="mb-4 text-xs font-medium text-green-600 sm:text-sm"
    >
        {{ status }}
    </div>

    <form @submit.prevent="submit">
        <FormInput
            id="email"
            v-model="form.email"
            label="Email"
            type="email"
            :error="form.errors.email"
            required
            autofocus
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
            autocomplete="current-password"
        />

        <div class="mt-4 block">
            <label class="flex items-center">
                <Checkbox v-model:checked="form.remember" name="remember" />
                <span class="ms-2 text-xs text-gray-600 sm:text-sm"
                    >Remember me</span
                >
            </label>
        </div>

        <div class="mt-4 flex items-center justify-end">
            <Link
                v-if="canResetPassword"
                :href="route('password.request')"
                class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden"
            >
                Forgot your password?
            </Link>

            <ButtonPrimary
                type="submit"
                class="ms-4"
                :disabled="form.processing"
                :loading="form.processing"
            >
                Log in
            </ButtonPrimary>
        </div>
    </form>
</template>
