<script setup lang="ts">
import ButtonClose from '@/Components/Base/Button/ButtonClose.vue';
import Modal from '@/Components/Base/Modal/Modal.vue';

interface Props {
    show: boolean;
    title: string;
    showGoogleDivider?: boolean;
}

defineProps<Props>();

const emit = defineEmits(['close', 'submit']);

const handleClose = () => {
    emit('close');
};

const handleSubmit = () => {
    emit('submit');
};
</script>

<template>
    <Modal :show="show" max-width="md" @close="handleClose">
        <div class="relative p-6">
            <ButtonClose @close="handleClose" />

            <h2 class="text-lg font-medium text-gray-900">{{ title }}</h2>

            <!-- Optional Google sign-in section -->
            <!--            <div v-if="showGoogleDivider" class="mt-6">-->
            <!--                <slot name="google-signin" />-->

            <!--                <div class="relative my-6">-->
            <!--                    <div class="absolute inset-0 flex items-center">-->
            <!--                        <div class="w-full border-t border-gray-300"></div>-->
            <!--                    </div>-->
            <!--                    <div class="relative flex justify-center text-sm">-->
            <!--                        <span class="bg-white px-2 text-gray-500"-->
            <!--                            >Or continue with email</span-->
            <!--                        >-->
            <!--                    </div>-->
            <!--                </div>-->
            <!--            </div>-->

            <!-- Status message (for forgot password) -->
            <slot name="status" />

            <!-- Form content -->
            <form
                :class="showGoogleDivider ? 'mt-6' : 'mt-6'"
                @submit.prevent="handleSubmit"
            >
                <slot name="fields" />

                <!-- Footer with navigation and submit button -->
                <div class="mt-6 flex items-center justify-between">
                    <slot name="footer-links" />
                    <slot name="submit-button" />
                </div>
            </form>
        </div>
    </Modal>
</template>
