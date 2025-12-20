<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import { useNotification } from '@/Composables/ui/useNotification';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);
const { success, error } = useNotification();

const form = useForm({
    currentPassword: '',
    password: '',
    passwordConfirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            success('Password updated successfully');
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'passwordConfirmation');
                passwordInput.value?.focus();
                error(String(form.errors.password));
            }
            if (form.errors.currentPassword) {
                form.reset('currentPassword');
                currentPasswordInput.value?.focus();
                error(String(form.errors.currentPassword));
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-indigo-900">Update Password</h2>

            <p class="mt-1 text-sm text-indigo-600">
                Ensure your account is using a long, random password to stay
                secure.
            </p>
        </header>

        <form class="mt-6 space-y-6" @submit.prevent="updatePassword">
            <FormInput
                id="current-password"
                v-model="form.currentPassword"
                class="max-w-sm"
                label="Current Password"
                type="password"
                :error="form.errors.currentPassword"
                autocomplete="current-password"
            />

            <FormInput
                id="password"
                v-model="form.password"
                class="max-w-sm"
                label="New Password"
                type="password"
                :error="form.errors.password"
                autocomplete="new-password"
            />

            <FormInput
                id="password-confirmation"
                v-model="form.passwordConfirmation"
                class="max-w-sm"
                label="Confirm Password"
                type="password"
                :error="form.errors.passwordConfirmation"
                autocomplete="new-password"
            />

            <div class="flex items-center gap-4">
                <ButtonPrimary
                    type="submit"
                    :disabled="form.processing"
                    :loading="form.processing"
                >
                    <DynamicIcon
                        name="arrow-down-tray"
                        class="mr-2 -ml-1 h-4 w-4"
                    />
                    Save
                </ButtonPrimary>
            </div>
        </form>
    </section>
</template>
