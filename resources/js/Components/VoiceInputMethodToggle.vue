<script setup lang="ts">
import FormToggle from '@/Components/FormToggle.vue';
import { computed } from 'vue';

interface Props {
    modelValue: boolean;
    disabled?: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
}>();

// Check if browser supports speech recognition
const speechRecognitionSupported = computed(() => {
    return !!(
        (window as any).SpeechRecognition ||
        (window as any).webkitSpeechRecognition
    );
});
</script>

<template>
    <!-- Voice input method toggle (only show if browser supports speech) -->
    <FormToggle
        v-if="speechRecognitionSupported"
        :model-value="modelValue"
        @update:model-value="(value) => emit('update:modelValue', value)"
        :disabled="disabled"
        label="Voice input method:"
        enabled-text="AI transcription (more accurate, slower)"
        disabled-text="Browser native (instant)"
    />
</template>
