<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';
import { useNotification } from '@/Composables/useNotification';
import { computed } from 'vue';

const { notifications, remove } = useNotification();

const typeConfig = computed(() => ({
    success: {
        bgColor: 'bg-green-50',
        borderColor: 'border-green-200',
        iconColor: 'text-green-400',
        textColor: 'text-green-800',
        hoverColor: 'hover:bg-green-100',
        ringColor: 'focus:ring-green-600',
        icon: 'check-circle',
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
    warning: {
        bgColor: 'bg-amber-50',
        borderColor: 'border-amber-200',
        iconColor: 'text-amber-400',
        textColor: 'text-amber-800',
        hoverColor: 'hover:bg-amber-100',
        ringColor: 'focus:ring-amber-600',
        icon: 'exclamation-triangle',
    },
    info: {
        bgColor: 'bg-blue-50',
        borderColor: 'border-blue-200',
        iconColor: 'text-blue-400',
        textColor: 'text-blue-800',
        hoverColor: 'hover:bg-blue-100',
        ringColor: 'focus:ring-blue-600',
        icon: 'information-circle',
    },
}));

const getTypeConfig = (type: string) => {
    return (
        typeConfig.value[type as keyof typeof typeConfig.value] ||
        typeConfig.value.info
    );
};
</script>

<template>
    <div class="fixed right-4 bottom-4 z-50 w-full max-w-md space-y-2">
        <Transition-group
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-x-2"
            enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
            tag="div"
            class="space-y-2"
        >
            <div
                v-for="notification in notifications"
                :key="notification.id"
                class="rounded-md border p-4 shadow-lg"
                :class="[
                    getTypeConfig(notification.type).bgColor,
                    getTypeConfig(notification.type).borderColor,
                ]"
            >
                <div class="flex items-center">
                    <div class="shrink-0">
                        <DynamicIcon
                            :name="getTypeConfig(notification.type).icon"
                            class="m-1.5 h-5 w-5"
                            :class="getTypeConfig(notification.type).iconColor"
                        />
                    </div>
                    <div class="ml-3 flex-1">
                        <p
                            class="text-sm font-medium"
                            :class="getTypeConfig(notification.type).textColor"
                        >
                            {{ notification.message }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="ml-3 inline-flex shrink-0 rounded-md p-1.5"
                        :class="[
                            getTypeConfig(notification.type).bgColor,
                            getTypeConfig(notification.type).iconColor,
                            getTypeConfig(notification.type).hoverColor,
                            `focus:ring-2 ${getTypeConfig(notification.type).ringColor} focus:ring-offset-2 focus:ring-offset-${getTypeConfig(notification.type).bgColor} focus:outline-hidden`,
                        ]"
                        @click="remove(notification.id)"
                    >
                        <DynamicIcon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </Transition-group>
    </div>
</template>
