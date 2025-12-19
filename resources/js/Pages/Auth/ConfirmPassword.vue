<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({
    layout: AppLayout,
});

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Confirm Password" />

    <div class="mb-4 text-sm text-gray-600">
        This is a secure area of the application. Please confirm your password
        before continuing.
    </div>

    <form @submit.prevent="submit">
        <FormInput
            id="password"
            v-model="form.password"
            label="Password"
            type="password"
            :error="form.errors.password"
            required
            autocomplete="current-password"
            autofocus
        />

        <div class="mt-4 flex justify-end">
            <ButtonPrimary
                type="submit"
                class="ms-4"
                :disabled="form.processing"
                :loading="form.processing"
            >
                Confirm
            </ButtonPrimary>
        </div>
    </form>
</template>
