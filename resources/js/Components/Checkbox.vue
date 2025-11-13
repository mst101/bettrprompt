<script setup lang="ts">
import { computed } from 'vue';

const emit = defineEmits<{
    (e: 'update:checked', value: boolean): void;
}>();

const props = defineProps<{
    checked: boolean;
    value?: string | number | boolean;
}>();

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val: boolean) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        class="rounded-sm border-gray-300 text-indigo-600 shadow-xs focus:ring-2 focus:ring-indigo-500 focus:ring-offset-gray-100 focus:outline-hidden"
    />
</template>
