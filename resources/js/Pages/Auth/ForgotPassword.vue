<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

defineOptions({
    layout: AppLayout,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot Password" />

    <div class="mb-4 text-sm text-gray-600">
        Forgot your password? No problem. Just let us know your email address
        and we will email you a password reset link that will allow you to
        choose a new one.
    </div>

    <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
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

        <div class="mt-4 flex items-center justify-end">
            <ButtonPrimary
                type="submit"
                :disabled="form.processing"
                :loading="form.processing"
            >
                Email Password Reset Link
            </ButtonPrimary>
        </div>
    </form>
</template>
