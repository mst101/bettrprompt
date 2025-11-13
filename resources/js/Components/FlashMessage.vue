<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

type MessageType = 'success' | 'warning' | 'error';

interface Props {
    message?: string;
    type?: MessageType;
    autoDismiss?: boolean;
    dismissDelay?: number;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'success',
    autoDismiss: true,
    dismissDelay: 5000,
});

const show = ref(!!props.message);
const messageElement = ref<HTMLElement | null>(null);

watch(
    () => props.message,
    (newMessage) => {
        if (newMessage) {
            show.value = true;
            if (props.autoDismiss) {
                setTimeout(() => {
                    show.value = false;
                }, props.dismissDelay);
            }
        }
    },
);

// Dismiss when clicking outside the message
const handleClickOutside = (event: MouseEvent) => {
    if (
        show.value &&
        messageElement.value &&
        !messageElement.value.contains(event.target as Node)
    ) {
        show.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const typeConfig = computed(() => {
    const configs = {
        success: {
            bgColor: 'bg-green-50',
            borderColor: 'border-green-200',
            iconColor: 'text-green-400',
            textColor: 'text-green-800',
            hoverColor: 'hover:bg-green-100',
            ringColor: 'focus:ring-green-600',
            icon: 'check-circle',
        },
        warning: {
            bgColor: 'bg-amber-50',
            borderColor: 'border-amber-200',
            iconColor: 'text-amber-400',
            textColor: 'text-amber-800',
            hoverColor: 'hover:bg-amber-100',
            ringColor: 'focus:ring-amber-600',
            icon: 'exclamation-triangle',
        },
        error: {
            bgColor: 'bg-red-50',
            borderColor: 'border-red-200',
            iconColor: 'text-red-400',
            textColor: 'text-red-800',
            hoverColor: 'hover:bg-red-100',
            ringColor: 'focus:ring-red-600',
            icon: 'x-circle',
        },
    };

    return configs[props.type];
});
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="message && show"
            ref="messageElement"
            class="fixed top-20 right-4 left-4 z-50 mx-auto max-w-xl sm:right-8 sm:left-auto"
        >
            <div
                class="rounded-md border p-4 shadow-lg"
                :class="[typeConfig.bgColor, typeConfig.borderColor]"
            >
                <div class="flex items-center justify-center">
                    <div class="shrink-0">
                        <DynamicIcon
                            :name="typeConfig.icon"
                            class="m-1.5 h-5 w-5"
                            :class="typeConfig.iconColor"
                        />
                    </div>
                    <div class="ml-3 flex-1">
                        <p
                            class="text-sm font-medium"
                            :class="typeConfig.textColor"
                        >
                            {{ message }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="ml-3 inline-flex shrink-0 rounded-md p-1.5"
                        :class="[
                            typeConfig.bgColor,
                            typeConfig.iconColor,
                            typeConfig.hoverColor,
                            `focus:ring-2 ${typeConfig.ringColor} focus:ring-offset-2 focus:ring-offset-${typeConfig.bgColor} focus:outline-hidden`,
                        ]"
                        @click="show = false"
                    >
                        <DynamicIcon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
