<!-- Move text/hover from DynamicIcon to the button; keep fill/stroke on the icon -->
<script setup lang="ts">
import ButtonClose from '@/Components/ButtonClose.vue';
import Checkbox from '@/Components/Checkbox.vue';
import GoogleSignInButton from '@/Components/GoogleSignInButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
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
    <Modal :show="show" @close="close" max-width="md">
        <div class="relative p-6">
            <ButtonClose @close="close" />

            <h2 class="text-lg font-medium text-gray-900">Log in</h2>

            <div class="mt-6">
                <GoogleSignInButton />

                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="bg-white px-2 text-gray-500"
                            >Or continue with email</span
                        >
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit">
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
                        <Checkbox
                            name="remember"
                            v-model:checked="form.remember"
                        />
                        <span class="ms-2 text-sm text-gray-600"
                            >Remember me</span
                        >
                    </label>
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <button
                            type="button"
                            @click="emit('switchToForgotPassword')"
                            class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Forgot password?
                        </button>

                        <button
                            type="button"
                            @click="emit('switchToRegister')"
                            class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Need an account?
                        </button>
                    </div>

                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Log in
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
