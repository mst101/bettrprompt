<script setup lang="ts">
import BaseAuthModal from '@/Components/BaseAuthModal.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
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
    <BaseAuthModal
        :show="show"
        title="Forgot Password"
        :show-google-divider="false"
        @close="close"
        @submit="submit"
    >
        <template #status>
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
        </template>

        <template #fields>
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
        </template>

        <template #footer-links>
            <button
                type="button"
                @click="emit('switchToLogin')"
                class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden"
            >
                Back to log in
            </button>
        </template>

        <template #submit-button>
            <ButtonPrimary
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Email Password Reset Link
            </ButtonPrimary>
        </template>
    </BaseAuthModal>
</template>
