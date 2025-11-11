<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import FormField from '@/Components/FormField.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    email: string;
    token: string;
}>();

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
    <Head title="Reset Password" />

    <form @submit.prevent="submit">
        <FormField
            id="email"
            v-model="form.email"
            label="Email"
            type="email"
            :error="form.errors.email"
            required
            autofocus
            autocomplete="username"
        />

        <FormField
            id="password"
            v-model="form.password"
            label="Password"
            type="password"
            :error="form.errors.password"
            class="mt-4"
            required
            autocomplete="new-password"
        />

        <FormField
            id="passwordConfirmation"
            v-model="form.passwordConfirmation"
            label="Confirm Password"
            type="password"
            :error="form.errors.passwordConfirmation"
            class="mt-4"
            required
            autocomplete="new-password"
        />

        <div class="mt-4 flex items-center justify-end">
            <ButtonPrimary
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Reset Password
            </ButtonPrimary>
        </div>
    </form>
</template>
