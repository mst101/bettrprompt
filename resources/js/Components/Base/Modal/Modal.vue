<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        show?: boolean;
        maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
        closeable?: boolean;
    }>(),
    {
        show: false,
        maxWidth: '2xl',
        closeable: true,
    },
);

const emit = defineEmits(['close']);
const dialog = ref();
const showSlot = ref(props.show);
const previouslyFocusedElement = ref<HTMLElement | null>(null);
const focusableSelectors = [
    'button',
    '[href]',
    'input',
    'select',
    'textarea',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');

const getFocusableElements = (): HTMLElement[] => {
    if (!dialog.value) return [];
    const focusableElements = dialog.value.querySelectorAll(focusableSelectors);
    return Array.from(focusableElements);
};

watch(
    () => props.show,
    () => {
        if (props.show) {
            // Store the currently focused element so we can restore it later
            previouslyFocusedElement.value =
                document.activeElement as HTMLElement;

            document.body.style.overflow = 'hidden';
            showSlot.value = true;

            dialog.value?.showModal();

            // Move focus to the first focusable element in the modal
            nextTick(() => {
                const focusableElements = getFocusableElements();
                if (focusableElements.length > 0) {
                    focusableElements[0].focus();
                } else {
                    // If no focusable elements, focus the dialog itself
                    dialog.value?.focus();
                }
            });
        } else {
            document.body.style.overflow = '';

            setTimeout(() => {
                dialog.value?.close();
                showSlot.value = false;

                // Restore focus to the previously focused element
                if (
                    previouslyFocusedElement.value &&
                    previouslyFocusedElement.value.focus
                ) {
                    previouslyFocusedElement.value.focus();
                }
            }, 200);
        }
    },
);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.show) {
        e.preventDefault();
        close();
    }
};

const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Tab' && props.show) {
        const focusableElements = getFocusableElements();
        if (focusableElements.length === 0) {
            e.preventDefault();
            return;
        }

        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        const activeElement = document.activeElement as HTMLElement;

        if (e.shiftKey && activeElement === firstElement) {
            e.preventDefault();
            lastElement.focus();
        } else if (!e.shiftKey && activeElement === lastElement) {
            e.preventDefault();
            firstElement.focus();
        }
    }
};

onMounted(() => {
    dialog.value?.addEventListener('keydown', closeOnEscape);
    dialog.value?.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    dialog.value?.removeEventListener('keydown', closeOnEscape);
    dialog.value?.removeEventListener('keydown', handleKeyDown);
    document.body.style.overflow = '';
});

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[props.maxWidth];
});
</script>

<template>
    <dialog
        ref="dialog"
        role="dialog"
        aria-modal="true"
        data-testid="modal-dialog"
        class="z-50 m-0 min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent"
    >
        <div
            class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
            scroll-region
        >
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-show="show"
                    class="fixed inset-0 transform transition-all"
                    @click="close"
                >
                    <div class="absolute inset-0 bg-gray-500 opacity-75" />
                </div>
            </Transition>

            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div
                    v-show="show"
                    class="relative mb-6 transform rounded-lg bg-white shadow-xl transition-all sm:mx-auto sm:w-full dark:bg-indigo-50"
                    :class="maxWidthClass"
                >
                    <slot v-if="showSlot" />
                </div>
            </Transition>
        </div>
    </dialog>
</template>
