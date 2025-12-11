<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue: string;
    title: string;
    placeholder?: string;
    readonly?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Enter content here...',
    readonly: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    expand: [];
    save: [];
}>();

const characterCount = computed(() => {
    return props.modelValue ? `${props.modelValue.length} characters` : 'N/A';
});
</script>

<template>
    <div class="flex flex-col overflow-hidden rounded-lg bg-white shadow-md">
        <div class="flex items-center justify-between bg-indigo-300 px-6 py-4">
            <span class="font-semibold text-indigo-800">{{ title }}</span>
            <div class="flex gap-2">
                <button
                    class="rounded bg-indigo-200 px-2 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-100"
                    title="Expand to full screen"
                    @click="emit('expand')"
                >
                    ⛶ Expand
                </button>
                <button
                    v-if="!readonly"
                    class="rounded bg-indigo-200 px-2 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-100"
                    title="Save to file"
                    @click="emit('save')"
                >
                    Save
                </button>
            </div>
        </div>
        <textarea
            :value="modelValue"
            :placeholder="placeholder"
            :readonly="readonly"
            class="flex-1 resize-none border-0 bg-indigo-100 p-6 font-mono text-xs text-black focus:outline-none"
            @input="
                emit(
                    'update:modelValue',
                    ($event.target as HTMLTextAreaElement).value,
                )
            "
        ></textarea>
        <div
            class="border-t border-indigo-200 bg-indigo-50 px-6 py-2 text-xs text-indigo-700"
        >
            {{ characterCount }}
        </div>
    </div>
</template>
