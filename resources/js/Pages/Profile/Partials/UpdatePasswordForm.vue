<script setup lang="ts">
import FormField from '@/Components/FormField.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);

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
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'passwordConfirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.currentPassword) {
                form.reset('currentPassword');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Update Password</h2>

            <p class="mt-1 text-sm text-gray-600">
                Ensure your account is using a long, random password to stay
                secure.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
            <FormField
                id="currentPassword"
                v-model="form.currentPassword"
                label="Current Password"
                type="password"
                :error="form.errors.currentPassword"
                autocomplete="current-password"
            />

            <FormField
                id="password"
                v-model="form.password"
                label="New Password"
                type="password"
                :error="form.errors.password"
                autocomplete="new-password"
            />

            <FormField
                id="passwordConfirmation"
                v-model="form.passwordConfirmation"
                label="Confirm Password"
                type="password"
                :error="form.errors.passwordConfirmation"
                autocomplete="new-password"
            />

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
