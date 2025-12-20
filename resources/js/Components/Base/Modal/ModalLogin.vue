<script setup lang="ts">
import ButtonGoogleSignIn from '@/Components/Base/Button/ButtonGoogleSignIn.vue';
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonText from '@/Components/Base/Button/ButtonText.vue';
import Checkbox from '@/Components/Base/Checkbox.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, watch } from 'vue';
import BaseAuthModal from './BaseAuthModal.vue';

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits([
    'close',
    'switchToRegister',
    'switchToForgotPassword',
]);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

// Focus the first field when modal opens
watch(
    () => props.show,
    async (newValue) => {
        if (newValue) {
            await nextTick();
            const inputElement = document.getElementById(
                'login-email',
            ) as HTMLInputElement;
            if (inputElement) {
                inputElement.focus();
            }
        }
    },
);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
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
        title="Log in"
        :show-google-divider="true"
        @close="close"
        @submit="submit"
    >
        <template #google-signin>
            <ButtonGoogleSignIn />
        </template>

        <template #fields>
            <FormInput
                id="login-email"
                v-model="form.email"
                label="Email"
                type="email"
                :error="form.errors.email"
                required
                autofocus
                autocomplete="username"
            />

            <FormInput
                id="login-password"
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
                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                </label>
            </div>
        </template>

        <template #footer-links>
            <div class="flex items-center">
                <ButtonText
                    id="forgot-password"
                    type="button"
                    @click="emit('switchToForgotPassword')"
                >
                    Forgot password?
                </ButtonText>

                <ButtonText
                    id="switch-to-register"
                    type="button"
                    @click="emit('switchToRegister')"
                >
                    Need an account?
                </ButtonText>
            </div>
        </template>

        <template #submit-button>
            <ButtonPrimary
                type="submit"
                :disabled="form.processing"
                :loading="form.processing"
                class="ml-4 whitespace-nowrap"
            >
                Log in
            </ButtonPrimary>
        </template>
    </BaseAuthModal>
</template>
