<script setup lang="ts">
import ButtonClose from '@/Components/ButtonClose.vue';
import FormField from '@/Components/FormField.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
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
    <Modal :show="show" @close="close" max-width="md">
        <div class="relative p-6">
            <ButtonClose @close="close" />

            <h2 class="text-lg font-medium text-gray-900">Register</h2>

            <form @submit.prevent="submit" class="mt-6">
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

                <div class="mt-6 flex items-center justify-between">
                    <button
                        type="button"
                        @click="emit('switchToLogin')"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Already registered?
                    </button>

                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Register
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
