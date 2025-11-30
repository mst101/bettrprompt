<script setup lang="ts">
import BaseAuthModal from '@/Components/BaseAuthModal.vue';
import ButtonGoogleSignIn from '@/Components/ButtonGoogleSignIn.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonText from '@/Components/ButtonText.vue';
import FormInput from '@/Components/FormInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, watch } from 'vue';

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits(['close', 'switchToLogin']);

const form = useForm({
    name: '',
    email: '',
    password: '',
    passwordConfirmation: '',
});

// Focus the first field when modal opens
watch(
    () => props.show,
    async (newValue) => {
        if (newValue) {
            await nextTick();
            const inputElement = document.getElementById(
                'register-name',
            ) as HTMLInputElement;
            if (inputElement) {
                inputElement.focus();
            }
        }
    },
);

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
            <FormInput
                id="register-name"
                v-model="form.name"
                label="Name"
                type="text"
                :error="form.errors.name"
                required
                autofocus
                autocomplete="name"
            />

            <FormInput
                id="register-email"
                v-model="form.email"
                label="Email"
                type="email"
                :error="form.errors.email"
                class="mt-4"
                required
                autocomplete="username"
            />

            <FormInput
                id="register-password"
                v-model="form.password"
                label="Password"
                type="password"
                :error="form.errors.password"
                class="mt-4"
                required
                autocomplete="new-password"
            />

            <FormInput
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
            <ButtonText
                id="switch-to-login"
                type="button"
                @click="emit('switchToLogin')"
            >
                Already registered?
            </ButtonText>
        </template>

        <template #submit-button>
            <ButtonPrimary
                class="ml-4 whitespace-nowrap"
                type="submit"
                :disabled="form.processing"
                :loading="form.processing"
            >
                Register
            </ButtonPrimary>
        </template>
    </BaseAuthModal>
</template>
