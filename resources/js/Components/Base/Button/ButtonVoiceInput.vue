<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import { useAudioRecording } from '@/Composables/features/useAudioRecording';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

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
const { t } = useI18n();

const isActive = computed(() => isRecording.value || isProcessing.value);
const displayError = computed(() => recordingError.value);

const buttonLabel = computed(() => {
    if (isProcessing.value) {
        return t('components.base.buttonVoiceInput.transcribing');
    }
    if (isActive.value) {
        return t('components.base.buttonVoiceInput.listening');
    }
    return t('components.base.buttonVoiceInput.record');
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
            :aria-label="
                isActive
                    ? t('components.base.buttonVoiceInput.stopRecording')
                    : t('components.base.buttonVoiceInput.startRecording')
            "
            class="inline-flex items-center justify-center gap-2 rounded-md border px-3 py-1.5 text-xs font-medium tracking-wider uppercase transition-colors duration-150 focus:ring-2 focus:ring-offset-2 focus:ring-offset-indigo-100 focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-75"
            :class="[
                isActive
                    ? 'animate-pulse bg-red-500 text-red-100 hover:bg-red-600 focus:animate-pulse focus:ring-red-600 dark:text-red-900'
                    : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 focus:ring-indigo-500 dark:bg-indigo-100 dark:text-indigo-900 dark:hover:bg-indigo-200',
            ]"
            :title="
                isActive
                    ? t('components.base.buttonVoiceInput.stopRecording')
                    : t('components.base.buttonVoiceInput.startRecordingTitle')
            "
            @click="toggleRecording"
        >
            <DynamicIcon
                v-if="!isProcessing"
                name="microphone"
                class="h-5 w-5"
                :class="
                    isActive
                        ? 'text-white dark:text-red-800'
                        : 'text-indigo-800'
                "
            />
            <DynamicIcon
                v-else
                name="arrow-path-spin"
                class="h-5 w-5 animate-spin text-red-100 dark:text-red-800"
            />
            <span>{{ buttonLabel }}</span>
        </button>

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
