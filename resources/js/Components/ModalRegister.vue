<script setup lang="ts">
import BaseAuthModal from '@/Components/BaseAuthModal.vue';
import ButtonGoogleSignIn from '@/Components/ButtonGoogleSignIn.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import FormField from '@/Components/FormField.vue';
import { useForm } from '@inertiajs/vue3';

defineProps<{
    show: boolean;
}>();

const emit = defineEmits(['close', 'switchToLogin']);

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
        onSuccess: () => {
            emit('close');
        },
    });
};

const close = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};
</script>

<template>
    <BaseAuthModal
        :show="show"
        title="Register"
        :show-google-divider="true"
        @close="close"
        @submit="submit"
    >
        <template #google-signin>
            <ButtonGoogleSignIn text="Sign up with Google" />
        </template>

        <template #fields>
            <FormField
                id="register-name"
                v-model="form.name"
                label="Name"
                type="text"
                :error="form.errors.name"
                required
                autofocus
                autocomplete="name"
            />

            <FormField
                id="register-email"
                v-model="form.email"
                label="Email"
                type="email"
                :error="form.errors.email"
                class="mt-4"
                required
                autocomplete="username"
            />

            <FormField
                id="register-password"
                v-model="form.password"
                label="Password"
                type="password"
                :error="form.errors.password"
                class="mt-4"
                required
                autocomplete="new-password"
            />

            <FormField
                id="register-password-confirmation"
                v-model="form.passwordConfirmation"
                label="Confirm Password"
                type="password"
                :error="form.errors.passwordConfirmation"
                class="mt-4"
                required
                autocomplete="new-password"
            />
        </template>

        <template #footer-links>
            <button
                type="button"
                @click="emit('switchToLogin')"
                class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden"
            >
                Already registered?
            </button>
        </template>

        <template #submit-button>
            <ButtonPrimary
                type="submit"
                :disabled="form.processing"
                :loading="form.processing"
            >
                Register
            </ButtonPrimary>
        </template>
    </BaseAuthModal>
</template>
