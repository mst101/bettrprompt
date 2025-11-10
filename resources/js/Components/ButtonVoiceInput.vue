<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';
import { useAudioRecording } from '@/Composables/useAudioRecording';
import { useSpeechRecognition } from '@/Composables/useSpeechRecognition';
import { computed, watch } from 'vue';

const props = defineProps<{
    forceWhisperAPI?: boolean;
}>();

const emit = defineEmits<{
    transcription: [text: string];
}>();

// Web Speech API (primary method for Chrome/Edge/Safari)
const {
    isSupported: speechSupported,
    isListening: speechListening,
    transcript: speechTranscript,
    interimTranscript: speechInterimTranscript,
    error: speechError,
    start: startSpeech,
    stop: stopSpeech,
} = useSpeechRecognition({
    lang: 'en-GB',
    continuous: true,
    interimResults: true,
});

// Audio recording fallback (for Firefox and unsupported browsers)
const {
    isRecording,
    isProcessing,
    error: recordingError,
    startRecording,
    stopRecording,
} = useAudioRecording();

// Determine which method to use
const useSpeechAPI = computed(() => {
    // If user prefers Whisper API, use it (even if browser supports speech)
    if (props.forceWhisperAPI) {
        return false;
    }
    // Otherwise use speech recognition if supported
    return speechSupported.value;
});

// Combined state
const isActive = computed(() =>
    useSpeechAPI.value
        ? speechListening.value
        : isRecording.value || isProcessing.value,
);

const displayError = computed(() => speechError.value || recordingError.value);

const buttonLabel = computed(() => {
    if (isProcessing.value) {
        return 'Transcribing...';
    }
    if (isActive.value) {
        return 'Listening...';
    }
    return 'Record';
});

// Watch for Web Speech API transcription (emit only new text)
let previousTranscriptLength = 0;
watch(speechTranscript, (newTranscript) => {
    if (!newTranscript) {
        // Reset tracking when transcript is cleared (new recording session)
        previousTranscriptLength = 0;
        return;
    }

    if (newTranscript.length > previousTranscriptLength) {
        // Emit only the new portion (delta)
        const newText = newTranscript.slice(previousTranscriptLength);
        previousTranscriptLength = newTranscript.length;
        emit('transcription', newText);
    }
});

const toggleRecording = async () => {
    if (useSpeechAPI.value) {
        // Use Web Speech API (real-time)
        if (speechListening.value) {
            stopSpeech();
        } else {
            startSpeech();
        }
    } else {
        // Use Audio Recording + Whisper API (batch)
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
    }
};
</script>

<template>
    <div class="relative">
        <button
            type="button"
            @click="toggleRecording"
            :disabled="isProcessing"
            class="rounded-md hover:text-indigo-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            :class="[
                'inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium shadow-xs transition-all',
                isActive
                    ? 'animate-pulse bg-red-600 text-white hover:bg-red-700'
                    : 'bg-white text-gray-700 ring-1 ring-gray-300 ring-inset hover:bg-gray-50',
                isProcessing ? 'cursor-wait opacity-75' : '',
            ]"
            :title="
                isActive
                    ? 'Stop recording'
                    : 'Click to record using your microphone'
            "
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

        <!-- Show interim transcript (Web Speech API only) -->
        <div
            v-if="useSpeechAPI && speechListening && speechInterimTranscript"
            class="absolute top-full left-0 z-10 mt-2 rounded-md bg-indigo-50 px-3 py-2 text-sm text-indigo-700 shadow-xs"
        >
            <div class="flex items-center gap-2">
                <div class="h-2 w-2 animate-pulse rounded-full bg-red-500" />
                <span class="italic">{{ speechInterimTranscript }}</span>
            </div>
        </div>

        <!-- Processing indicator (Whisper API) -->
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
