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
            class="rounded-md text-xs tracking-wider uppercase hover:text-indigo-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            :class="[
                'inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium shadow-xs transition-all',
                isActive
                    ? 'animate-pulse bg-red-600 text-white hover:bg-red-700'
                    : 'bg-white text-gray-700 ring-1 ring-gray-300 ring-inset hover:bg-gray-50',
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
                :class="['h-5 w-5', isActive ? 'text-white' : 'text-gray-600']"
            />
            <DynamicIcon
                v-else
                name="arrow-path-spin"
                class="h-5 w-5 animate-spin text-gray-600"
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
