<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
import VoiceInputButton from '@/Components/VoiceInputButton.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

defineOptions({
    layout: AppLayout,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const hasPersonalityType = computed(() => !!user.value?.personalityType);
const hasTask = computed(() => form.taskDescription.length >= 10);

const form = useForm({
    taskDescription: '',
});

const submit = () => {
    form.post(route('prompt-optimizer.store'));
};

const handleTranscription = (text: string) => {
    // Append transcription to existing text (with space if text exists)
    if (form.taskDescription && !form.taskDescription.endsWith(' ')) {
        form.taskDescription += ' ';
    }
    form.taskDescription += text;
};

const clearTaskDescription = () => {
    form.taskDescription = '';
};

// Voice input method preference (when browser supports both)
// Default to true (Whisper API) for better accuracy
const preferWhisperAPI = ref(
    localStorage.getItem('preferWhisperAPI') !== null
        ? localStorage.getItem('preferWhisperAPI') === 'true'
        : true,
);

// Persist preference changes to localStorage
watch(preferWhisperAPI, (newValue) => {
    localStorage.setItem('preferWhisperAPI', String(newValue));
});

// Check if browser supports speech recognition
const speechRecognitionSupported = computed(() => {
    return !!(
        (window as any).SpeechRecognition ||
        (window as any).webkitSpeechRecognition
    );
});
</script>

<template>
    <Head title="Prompt Optimiser" />

    <header class="bg-white shadow">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Prompt Optimiser
            </h2>
        </div>
    </header>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Warning message if no personality type -->
                    <div
                        v-if="!hasPersonalityType"
                        class="mb-6 rounded-md border border-amber-200 bg-amber-50 p-4"
                    >
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <DynamicIcon
                                    name="exclamation-triangle"
                                    class="h-5 w-5 text-amber-400"
                                />
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-amber-800">
                                    Personality type required
                                </h3>
                                <div class="mt-2 text-sm text-amber-700">
                                    <p>
                                        Please
                                        <Link
                                            :href="route('profile.edit')"
                                            class="font-medium underline hover:text-amber-600"
                                        >
                                            enter your personality type
                                        </Link>
                                        on your profile page before submitting a
                                        task description.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="mb-6 text-gray-600">
                        Create optimised AI prompts customised to your specific
                        task requirements and personality type.
                    </p>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Task Description -->
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label
                                    for="taskDescription"
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    Task Description
                                </label>
                                <div
                                    v-if="hasPersonalityType"
                                    class="flex items-center gap-2"
                                >
                                    <button
                                        v-if="form.taskDescription"
                                        type="button"
                                        @click="clearTaskDescription"
                                        class="inline-flex items-center gap-2 rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50"
                                        title="Clear text"
                                    >
                                        <DynamicIcon
                                            name="trash"
                                            class="h-5 w-5 text-gray-600"
                                        />
                                        <span>Clear</span>
                                    </button>
                                    <VoiceInputButton
                                        @transcription="handleTranscription"
                                        :force-whisper-a-p-i="preferWhisperAPI"
                                    />
                                </div>
                            </div>
                            <textarea
                                id="taskDescription"
                                v-model="form.taskDescription"
                                :disabled="!hasPersonalityType"
                                required
                                autofocus
                                rows="6"
                                placeholder="Describe what you're trying to accomplish..."
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500"
                            ></textarea>
                            <p
                                v-if="form.errors.taskDescription"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.taskDescription }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500">
                                Minimum 10 characters. Be specific about your
                                goals and requirements.
                            </p>

                            <!-- Voice input method toggle (only show if browser supports speech) -->
                            <ToggleSwitch
                                v-if="
                                    hasPersonalityType &&
                                    speechRecognitionSupported
                                "
                                v-model="preferWhisperAPI"
                                label="Voice input method:"
                                enabled-text="AI transcription (more accurate, slower)"
                                disabled-text="Browser native (instant)"
                                class="mt-3"
                            />
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end">
                            <button
                                type="submit"
                                :disabled="
                                    !hasPersonalityType ||
                                    form.processing ||
                                    !hasTask
                                "
                                class="justify-centre inline-flex rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span v-if="form.processing"
                                    >Processing...</span
                                >
                                <span v-else>Optimise Prompt</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
