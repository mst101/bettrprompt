<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';

interface Props {
    title: string;
    value: string | number;
    icon: string;
    iconColour?: string;
    change?: number;
    changeType?: 'increase' | 'decrease';
}

const props = withDefaults(defineProps<Props>(), {
    iconColour: 'blue',
    change: undefined,
    changeType: 'increase',
});

const iconBgClasses = {
    blue: 'bg-blue-100 text-blue-600',
    green: 'bg-green-100 text-green-600',
    purple: 'bg-purple-100 text-purple-600',
    indigo: 'bg-indigo-100 text-indigo-600',
    orange: 'bg-orange-100 text-orange-600',
    red: 'bg-red-100 text-red-600',
};

const getChangeClasses = () => {
    if (props.changeType === 'increase') {
        return 'text-green-600';
    }
    return 'text-red-600';
};
</script>

<template>
    <Card>
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div
                    :class="[
                        'rounded-lg p-3',
                        iconBgClasses[iconColour] || iconBgClasses.blue,
                    ]"
                >
                    <DynamicIcon :name="icon" class="h-6 w-6" />
                </div>
                <div class="ml-4">
                    <p class="text-xs text-indigo-500 sm:text-sm">
                        {{ title }}
                    </p>
                    <p
                        class="text-lg font-semibold text-indigo-900 sm:text-2xl"
                    >
                        {{ value }}
                    </p>
                </div>
            </div>
            <div v-if="change !== undefined" class="ml-2">
                <span :class="['text-sm font-medium', getChangeClasses()]">
                    {{ change > 0 ? '+' : '' }}{{ change }}%
                </span>
            </div>
        </div>
    </Card>
</template>
