<script setup lang="ts">
import Card from '@/Components/Card.vue';
import FormField from '@/Components/FormField.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
import VoiceInputButton from '@/Components/VoiceInputButton.vue';
import { useLocalStorage } from '@/Composables/useLocalStorage';
import { computed } from 'vue';

interface Props {
    question: string;
    answer: string;
    currentQuestionNumber: number;
    totalQuestions: number;
    isSubmitting: boolean;
    hasError?: boolean;
    errorMessage?: string;
}

interface Emits {
    (e: 'update:answer', value: string): void;
    (e: 'submit'): void;
    (e: 'skip'): void;
    (e: 'clear'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Voice input method preference
const preferWhisperAPI = useLocalStorage('preferWhisperAPI', true);

// Check if browser supports speech recognition
const speechRecognitionSupported = computed(() => {
    return !!(
        (window as any).SpeechRecognition ||
        (window as any).webkitSpeechRecognition
    );
});

const handleTranscription = (text: string) => {
    let newAnswer = props.answer;
    if (newAnswer && !newAnswer.endsWith(' ')) {
        newAnswer += ' ';
    }
    newAnswer += text;
    emit('update:answer', newAnswer);
};

const progressPercent = computed(() => {
    if (props.totalQuestions === 0) return 0;
    return (props.currentQuestionNumber / props.totalQuestions) * 100;
});
</script>

<template>
    <Card>
        <div class="space-y-6">
            <!-- Progress -->
            <div data-testid="progress-indicator">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-gray-700"
                        >Question {{ currentQuestionNumber }} of
                        {{ totalQuestions }}</span
                    >
                    <span class="text-gray-500"
                        >{{ Math.round(progressPercent) }}% complete</span
                    >
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200">
                    <div
                        data-testid="progress-bar"
                        class="h-full bg-indigo-600 transition-all duration-300"
                        :style="{ width: `${progressPercent}%` }"
                    ></div>
                </div>
            </div>

            <!-- Question -->
            <div class="rounded-lg bg-indigo-50 p-4">
                <p class="text-sm font-medium text-indigo-900">
                    {{ question }}
                </p>
            </div>

            <!-- Answer Input -->
            <FormField
                id="answer"
                label="Your Answer"
                :error="hasError ? errorMessage : undefined"
            >
                <textarea
                    :value="answer"
                    @input="
                        emit(
                            'update:answer',
                            ($event.target as HTMLTextAreaElement).value,
                        )
                    "
                    rows="4"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    :disabled="isSubmitting"
                    placeholder="Type your answer here..."
                ></textarea>

                <!-- Voice Input Controls -->
                <div class="mt-3 flex items-center justify-between">
                    <VoiceInputButton
                        @transcription="handleTranscription"
                        :prefer-whisper-a-p-i="preferWhisperAPI"
                        :disabled="isSubmitting"
                    />

                    <div class="flex items-center gap-6">
                        <!-- Voice Method Toggle (only show if browser supports both) -->
                        <div
                            v-if="speechRecognitionSupported"
                            class="flex items-center gap-2"
                        >
                            <span class="text-sm text-gray-600">Browser</span>
                            <ToggleSwitch
                                v-model="preferWhisperAPI"
                                :disabled="isSubmitting"
                            />
                            <span class="text-sm text-gray-600"
                                >Whisper API</span
                            >
                        </div>

                        <!-- Clear Button -->
                        <button
                            v-if="answer"
                            @click="emit('clear')"
                            type="button"
                            class="text-sm text-gray-500 hover:text-gray-700"
                            :disabled="isSubmitting"
                        >
                            Clear
                        </button>
                    </div>
                </div>
            </FormField>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <PrimaryButton
                    data-testid="submit-answer-button"
                    @click="emit('submit')"
                    :disabled="!answer.trim() || isSubmitting"
                    class="flex-1"
                >
                    <LoadingSpinner v-if="isSubmitting" class="mr-2" />
                    Submit Answer
                </PrimaryButton>

                <SecondaryButton
                    data-testid="skip-question-button"
                    @click="emit('skip')"
                    :disabled="isSubmitting"
                >
                    Skip Question
                </SecondaryButton>
            </div>
        </div>
    </Card>
</template>
