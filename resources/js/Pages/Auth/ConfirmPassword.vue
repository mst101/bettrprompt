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
    <Head :title="$t('auth.confirmPassword.title')" />

    <div class="mb-4 text-sm text-indigo-600">
        {{ $t('auth.confirmPassword.description') }}
    </div>

    <form @submit.prevent="submit">
        <FormInput
            id="password"
            v-model="form.password"
            :label="$t('auth.confirmPassword.passwordLabel')"
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
                {{ $t('common.buttons.confirm') }}
            </ButtonPrimary>
        </div>
    </form>
</template>
