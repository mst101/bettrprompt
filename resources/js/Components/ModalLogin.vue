<script setup lang="ts">
import BaseAuthModal from '@/Components/BaseAuthModal.vue';
import ButtonGoogleSignIn from '@/Components/ButtonGoogleSignIn.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import Checkbox from '@/Components/Checkbox.vue';
import FormInput from '@/Components/FormInput.vue';
import { useForm } from '@inertiajs/vue3';

defineProps<{
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
                label="Email"
                class="bg-green-300 text-pink-500"
                type="email"
                v-model="form.email"
                :error="form.errors.email"
                required
                autofocus
                autocomplete="username"
            />

            <FormInput
                id="login-password"
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
        </template>

        <template #footer-links>
            <div class="flex items-center gap-4">
                <button
                    type="button"
                    @click="emit('switchToForgotPassword')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden"
                >
                    Forgot password?
                </button>

                <button
                    type="button"
                    @click="emit('switchToRegister')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden"
                >
                    Need an account?
                </button>
            </div>
        </template>

        <template #submit-button>
            <ButtonPrimary
                type="submit"
                :disabled="form.processing"
                :loading="form.processing"
            >
                Log in
            </ButtonPrimary>
        </template>
    </BaseAuthModal>
</template>
