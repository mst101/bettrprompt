<script setup lang="ts">
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import ContainerPage from '@/Components/ContainerPage.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import FormTextareaWithActions from '@/Components/FormTextareaWithActions.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import { useTextAppend } from '@/Composables/useTextAppend';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const { appendText } = useTextAppend();

const handleTranscription = (text: string) => {
    form.taskDescription = appendText(form.taskDescription, text);
};

const clearTaskDescription = () => {
    form.taskDescription = '';
};
</script>

<template>
    <Head title="Prompt Optimiser" />

    <HeaderPage title="Prompt Optimiser" />

    <ContainerPage>
        <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
            <div class="p-6">
                <!-- Info message if no personality type -->
                <div
                    v-if="!hasPersonalityType"
                    class="mb-6 rounded-md border border-blue-200 bg-blue-50 p-4"
                >
                    <div class="flex">
                        <div class="shrink-0">
                            <DynamicIcon
                                name="information-circle"
                                class="h-5 w-5 text-blue-400"
                            />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">
                                Get personalised prompts
                            </h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>
                                    For personalised prompts tailored to your
                                    communication style,
                                    <Link
                                        :href="route('profile.edit')"
                                        class="font-medium underline hover:text-blue-600"
                                    >
                                        add your personality type
                                    </Link>
                                    to your profile. Otherwise, we'll select the
                                    best framework based purely on your task.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="mb-6 text-gray-600">
                    Create optimised AI prompts using expert frameworks.
                    <span v-if="hasPersonalityType">
                        Prompts will be customised to your personality type and
                        task requirements.
                    </span>
                    <span v-else>
                        Prompts will be optimised for your specific task
                        requirements.
                    </span>
                </p>

                <form class="space-y-6" @submit.prevent="submit">
                    <!-- Task Description -->
                    <FormTextareaWithActions
                        id="taskDescription"
                        v-model="form.taskDescription"
                        label="Task Description"
                        :error="form.errors.taskDescription"
                        help-text="Minimum 10 characters. Be specific about your goals and requirements."
                        required
                        autofocus
                        :rows="6"
                        placeholder="Describe what you're trying to accomplish..."
                    >
                        <template #actions>
                            <button
                                v-if="form.taskDescription"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-xs ring-1 ring-gray-300 transition ring-inset hover:bg-gray-50"
                                title="Clear text"
                                @click="clearTaskDescription"
                            >
                                <DynamicIcon
                                    name="trash"
                                    class="h-5 w-5 text-gray-600"
                                />
                                <span>Clear</span>
                            </button>
                            <ButtonVoiceInput
                                @transcription="handleTranscription"
                            />
                        </template>
                    </FormTextareaWithActions>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing || !hasTask"
                            class="justify-centre inline-flex rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium tracking-wide text-white uppercase shadow-xs hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span v-if="form.processing">Processing...</span>
                            <span v-else>Optimise Prompt</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ContainerPage>
</template>
