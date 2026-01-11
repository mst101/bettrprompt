<script setup lang="ts">
import ButtonDanger from '@/Components/Base/Button/ButtonDanger.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import CollapsibleSection from '@/Components/Base/CollapsibleSection.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import Modal from '@/Components/Base/Modal/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(countryRoute('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => {
            form.reset();
        },
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <CollapsibleSection
        :title="$t('profile.deleteAccount.title')"
        :subtitle="$t('profile.deleteAccount.subtitle')"
        data-testid="delete-account"
        icon="trash"
    >
        <div class="space-y-6">
            <ButtonDanger icon="trash" @click="confirmUserDeletion">
                {{ $t('profile.deleteAccount.actions.open') }}
            </ButtonDanger>

            <Modal :show="confirmingUserDeletion" @close="closeModal">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-indigo-900">
                        {{ $t('profile.deleteAccount.modal.title') }}
                    </h2>

                    <p class="mt-1 text-sm text-indigo-600">
                        {{ $t('profile.deleteAccount.modal.description') }}
                    </p>

                    <div class="mt-6">
                        <FormInput
                            id="password"
                            v-model="form.password"
                            :label="$t('auth.login.password')"
                            type="password"
                            :error="form.errors.password"
                            :placeholder="$t('auth.login.password')"
                            class="w-3/4"
                            @keyup.enter="deleteUser"
                        />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <ButtonSecondary @click="closeModal">
                            {{ $t('common.buttons.cancel') }}
                        </ButtonSecondary>

                        <ButtonDanger
                            class="ms-3"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                            icon="trash"
                            @click="deleteUser"
                        >
                            {{ $t('profile.deleteAccount.actions.confirm') }}
                        </ButtonDanger>
                    </div>
                </div>
            </Modal>
        </div>
    </CollapsibleSection>
</template>
