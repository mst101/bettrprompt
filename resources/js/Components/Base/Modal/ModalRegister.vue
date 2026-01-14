<script setup lang="ts">
import ButtonGoogleSignIn from '@/Components/Base/Button/ButtonGoogleSignIn.vue';
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonText from '@/Components/Base/Button/ButtonText.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import { analyticsService } from '@/services/analytics';
import { useForm } from '@inertiajs/vue3';
import { nextTick, watch } from 'vue';
import BaseAuthModal from './BaseAuthModal.vue';

const props = defineProps<{
    show: boolean;
    email?: string;
}>();

const emit = defineEmits(['close', 'switchToLogin']);

const form = useForm({
    name: '',
    email: props.email || '',
    password: '',
    passwordConfirmation: '',
});

// Update form when email prop changes
watch(
    () => props.email,
    (newEmail) => {
        if (newEmail) {
            form.email = newEmail;
        }
    },
);

// Focus the first field when modal opens
watch(
    () => props.show,
    async (newValue) => {
        if (newValue) {
            // Track registration started (fresh attempt)
            if (!form.name && !form.email) {
                analyticsService.track({
                    name: 'registration_started',
                    properties: {
                        modal_opened: true,
                    },
                });
            }

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
        :title="$t('auth.modalRegister.title')"
        :show-google-divider="true"
        @close="close"
        @submit="submit"
    >
        <template #google-signin>
            <ButtonGoogleSignIn :text="$t('auth.modalRegister.google')" />
        </template>

        <template #fields>
            <FormInput
                id="register-name"
                v-model="form.name"
                :label="$t('auth.modalRegister.nameLabel')"
                type="text"
                :error="form.errors.name"
                required
                autofocus
                autocomplete="name"
            />

            <FormInput
                id="register-email"
                v-model="form.email"
                :label="$t('auth.modalRegister.emailLabel')"
                type="email"
                :error="form.errors.email"
                class="mt-4"
                required
                autocomplete="username"
            />

            <FormInput
                id="register-password"
                v-model="form.password"
                :label="$t('auth.modalRegister.passwordLabel')"
                type="password"
                :error="form.errors.password"
                class="mt-4"
                required
                autocomplete="new-password"
            />

            <FormInput
                id="register-password-confirmation"
                v-model="form.passwordConfirmation"
                :label="$t('auth.modalRegister.confirmPasswordLabel')"
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
                @click="emit('switchToLogin', form.email)"
            >
                {{ $t('auth.modalRegister.alreadyRegistered') }}
            </ButtonText>
        </template>

        <template #submit-button>
            <ButtonPrimary
                class="ml-4 whitespace-nowrap"
                type="submit"
                :disabled="form.processing"
                :loading="form.processing"
            >
                {{ $t('auth.modalRegister.submit') }}
            </ButtonPrimary>
        </template>
    </BaseAuthModal>
</template>
