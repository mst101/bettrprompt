<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import CollapsibleSection from '@/Components/Base/CollapsibleSection.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import { useNotification } from '@/Composables/ui/useNotification';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);
const { success, error } = useNotification();
const { t } = useI18n({ useScope: 'global' });

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
            success(t('profile.password.notifications.updated'));
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
        <CollapsibleSection
            :title="$t('profile.password.title')"
            :subtitle="$t('profile.password.subtitle')"
            data-testid="password"
            icon="lock-closed"
        >
            <form class="space-y-6" @submit.prevent="updatePassword">
                <FormInput
                    id="current-password"
                    v-model="form.currentPassword"
                    class="max-w-sm"
                    :label="$t('profile.password.fields.current')"
                    type="password"
                    :error="form.errors.currentPassword"
                    autocomplete="current-password"
                />

                <FormInput
                    id="password"
                    v-model="form.password"
                    class="max-w-sm"
                    :label="$t('profile.password.fields.new')"
                    type="password"
                    :error="form.errors.password"
                    autocomplete="new-password"
                />

                <FormInput
                    id="password-confirmation"
                    v-model="form.passwordConfirmation"
                    class="max-w-sm"
                    :label="$t('profile.password.fields.confirm')"
                    type="password"
                    :error="form.errors.passwordConfirmation"
                    autocomplete="new-password"
                />

                <div class="flex items-center gap-4">
                    <ButtonPrimary
                        type="submit"
                        :disabled="form.processing"
                        :loading="form.processing"
                        icon="download"
                    >
                        {{ $t('common.buttons.save') }}
                    </ButtonPrimary>
                </div>
            </form>
        </CollapsibleSection>
    </section>
</template>
