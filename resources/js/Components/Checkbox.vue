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
        class="rounded-sm border-gray-300 bg-white text-black shadow-xs focus:ring-indigo-500 focus:outline-hidden"
    />
</template>
