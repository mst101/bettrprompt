<script setup lang="ts">
import ButtonClose from '@/Components/ButtonClose.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';

defineProps<{
    show: boolean;
    status?: string;
}>();

const emit = defineEmits(['close', 'switchToLogin']);

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'), {
        onFinish: () => {
            // Don't reset email on finish so user can see what they submitted
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

            <h2 class="text-lg font-medium text-gray-900">Forgot Password</h2>

            <div class="mt-4 text-sm text-gray-600">
                Forgot your password? No problem. Just let us know your email
                address and we will email you a password reset link that will
                allow you to choose a new one.
            </div>

            <div
                v-if="status"
                class="mt-4 rounded-md bg-green-50 p-3 text-sm font-medium text-green-600"
            >
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="mt-6">
                <div>
                    <InputLabel for="forgot-password-email" value="Email" />

                    <TextInput
                        id="forgot-password-email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <button
                        type="button"
                        @click="emit('switchToLogin')"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Back to log in
                    </button>

                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Email Password Reset Link
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
