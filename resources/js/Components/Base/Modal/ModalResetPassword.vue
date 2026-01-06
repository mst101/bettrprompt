<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonText from '@/Components/Base/Button/ButtonText.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, watch } from 'vue';
import BaseAuthModal from './BaseAuthModal.vue';

const props = defineProps<{
    show: boolean;
    email?: string;
    token?: string;
}>();

const emit = defineEmits(['close', 'switchToLogin']);

const form = useForm({
    token: props.token || '',
    email: props.email || '',
    password: '',
    passwordConfirmation: '',
});

// Update form when props change
watch(
    () => [props.email, props.token],
    ([newEmail, newToken]) => {
        if (newEmail) form.email = newEmail;
        if (newToken) form.token = newToken;
    },
);

// Focus the password field when modal opens (email is pre-populated)
watch(
    () => props.show,
    async (newValue) => {
        if (newValue) {
            await nextTick();
            const inputElement = document.getElementById(
                'reset-password-password',
            ) as HTMLInputElement;
            if (inputElement) {
                inputElement.focus();
            }
        }
    },
);

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'passwordConfirmation');
        },
        onSuccess: () => {
            close();
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
        title="Create New Password"
        :show-google-divider="false"
        @close="close"
        @submit="submit"
    >
        <template #status>
            <div class="mt-4 text-sm text-indigo-600">
                Please enter your email address and choose a new password.
            </div>
        </template>

        <template #fields>
            <FormInput
                id="reset-password-email"
                v-model="form.email"
                label="Email"
                type="email"
                :error="form.errors.email"
                required
                autocomplete="username"
            />

            <FormInput
                id="reset-password-password"
                v-model="form.password"
                label="Password"
                type="password"
                :error="form.errors.password"
                class="mt-4"
                required
                autofocus
                autocomplete="new-password"
            />

            <FormInput
                id="reset-password-confirmation"
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
            <ButtonText
                id="switch-to-login-from-reset"
                type="button"
                @click="emit('switchToLogin', form.email)"
            >
                Back to log in
            </ButtonText>
        </template>

        <template #submit-button>
            <ButtonPrimary
                type="submit"
                :disabled="form.processing"
                :loading="form.processing"
                icon="check"
            >
                Reset Password
            </ButtonPrimary>
        </template>
    </BaseAuthModal>
</template>
