<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';
import { useAudioRecording } from '@/Composables/useAudioRecording';
import { computed } from 'vue';

defineProps<{
    disabled?: boolean;
}>();

const emit = defineEmits<{
    transcription: [text: string];
}>();

// Always use Whisper API via audio recording
const {
    isRecording,
    isProcessing,
    error: recordingError,
    startRecording,
    stopRecording,
} = useAudioRecording();

const isActive = computed(() => isRecording.value || isProcessing.value);
const displayError = computed(() => recordingError.value);

const buttonLabel = computed(() => {
    if (isProcessing.value) {
        return 'Transcribing...';
    }
    if (isActive.value) {
        return 'Listening...';
    }
    return 'Record';
});

const toggleRecording = async () => {
    if (isRecording.value) {
        try {
            const transcript = await stopRecording();
            emit('transcription', transcript);
        } catch (err) {
            console.error('Recording error:', err);
        }
    } else {
        startRecording();
    }
};
</script>

<template>
    <div class="relative">
        <button
            type="button"
            :disabled="isProcessing || disabled"
            class="inline-flex items-center justify-center gap-2 rounded-md border px-3 py-1.5 text-sm font-medium transition-colors duration-150 focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50"
            :class="[
                isActive
                    ? 'animate-pulse bg-red-600 hover:bg-red-700 focus:animate-pulse focus:ring-red-600'
                    : 'bg-white text-gray-700 hover:bg-gray-50 focus:ring-indigo-500 dark:bg-indigo-100 dark:hover:bg-indigo-200',
                isProcessing || disabled ? 'cursor-not-allowed opacity-50' : '',
            ]"
            :title="
                isActive
                    ? 'Stop recording'
                    : 'Click to record using your microphone'
            "
            @click="toggleRecording"
        >
            <DynamicIcon
                v-if="!isProcessing"
                name="microphone"
                class="h-5 w-5"
                :class="isActive ? 'text-white' : 'text-gray-600'"
            />
            <DynamicIcon
                v-else
                name="arrow-path-spin"
                class="h-5 w-5 animate-spin text-white"
            />
            <span>{{ buttonLabel }}</span>
        </button>

        <!-- Processing indicator -->
        <div
            v-if="isProcessing"
            class="absolute top-full left-0 z-10 mt-2 rounded-md bg-indigo-50 px-3 py-2 text-sm text-indigo-700 shadow-xs"
        >
            <div class="flex items-center gap-2">
                <DynamicIcon
                    name="arrow-path-spin"
                    class="h-4 w-4 animate-spin"
                />
                <span>Transcribing your audio...</span>
            </div>
        </div>

        <!-- Error message -->
        <div
            v-if="displayError"
            class="absolute top-full left-0 z-10 mt-2 max-w-xs rounded-md bg-red-50 px-3 py-2 text-sm text-red-700 shadow-xs"
        >
            <div class="flex items-center gap-2">
                <DynamicIcon
                    name="exclamation-triangle"
                    class="h-4 w-4 shrink-0"
                />
                <span>{{ displayError }}</span>
            </div>
        </div>
    </div>
</template>
