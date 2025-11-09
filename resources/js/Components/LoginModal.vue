<script setup lang="ts">
import BaseAuthModal from '@/Components/BaseAuthModal.vue';
import Checkbox from '@/Components/Checkbox.vue';
import GoogleSignInButton from '@/Components/GoogleSignInButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
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
            <GoogleSignInButton />
        </template>

        <template #fields>
            <div>
                <InputLabel for="login-email" value="Email" />

                <TextInput
                    id="login-email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="login-password" value="Password" />

                <TextInput
                    id="login-password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

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
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Forgot password?
                </button>

                <button
                    type="button"
                    @click="emit('switchToRegister')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Need an account?
                </button>
            </div>
        </template>

        <template #submit-button>
            <PrimaryButton
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Log in
            </PrimaryButton>
        </template>
    </BaseAuthModal>
</template>
