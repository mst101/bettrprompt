<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import Checkbox from '@/Components/Checkbox.vue';
import FormInput from '@/Components/FormInput.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({
    layout: AppLayout,
});

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

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

    <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
        {{ status }}
    </div>

    <form @submit.prevent="submit">
        <FormInput
            id="email"
            label="Email"
            type="email"
            v-model="form.email"
            :error="form.errors.email"
            required
            autofocus
            autocomplete="username"
        />

        <FormInput
            id="password"
            label="Password"
            type="password"
            v-model="form.password"
            :error="form.errors.password"
            class="mt-4"
            required
            autocomplete="current-password"
        />

        <div class="mt-4 block">
            <label class="flex items-center">
                <Checkbox name="remember" v-model:checked="form.remember" />
                <span class="ms-2 text-sm text-gray-600">Remember me</span>
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
