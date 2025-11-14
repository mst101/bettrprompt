<script setup lang="ts">
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import ContainerPage from '@/Components/ContainerPage.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import FormTextareaWithActions from '@/Components/FormTextareaWithActions.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import { useTextAppend } from '@/Composables/useTextAppend';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    visitorPersonalityType?: string | null;
    visitorTraitPercentages?: Record<string, number> | null;
}

const props = defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const hasPersonalityType = computed(() => {
    // Authenticated users check their user profile
    if (user.value) {
        return !!user.value?.personalityType;
    }
    // Visitors check props passed from controller
    return !!props.visitorPersonalityType;
});
const hasTask = computed(() => form.taskDescription.length >= 10);

const showPersonalityForm = ref(false);

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

// Visitor personality form
const personalityForm = useForm({
    personality_type: props.visitorPersonalityType || '',
    trait_percentages: props.visitorTraitPercentages || null,
});

const savePersonalityType = () => {
    personalityForm.patch(route('visitor.personality.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showPersonalityForm.value = false;
        },
    });
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
                                Get personalised prompts (optional)
                            </h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <!-- Authenticated user -->
                                <p v-if="user">
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
                                <!-- Visitor -->
                                <div v-else>
                                    <p class="mb-2">
                                        For personalised prompts tailored to
                                        your communication style, add your
                                        16personalities type. Otherwise, we'll
                                        select the best framework based purely
                                        on your task.
                                    </p>
                                    <button
                                        v-if="!showPersonalityForm"
                                        type="button"
                                        class="font-medium underline hover:text-blue-600"
                                        @click="showPersonalityForm = true"
                                    >
                                        Add personality type
                                    </button>
                                    <div v-else class="mt-3 space-y-3">
                                        <div>
                                            <label
                                                for="visitor-personality-type"
                                                class="block text-sm font-medium text-blue-900"
                                            >
                                                Personality Type (e.g., INTJ-T)
                                            </label>
                                            <input
                                                id="visitor-personality-type"
                                                v-model="
                                                    personalityForm.personality_type
                                                "
                                                type="text"
                                                placeholder="XXXX-X"
                                                maxlength="6"
                                                class="mt-1 block w-full max-w-xs rounded-md border-blue-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p
                                                v-if="
                                                    personalityForm.errors
                                                        .personality_type
                                                "
                                                class="mt-1 text-sm text-red-600"
                                            >
                                                {{
                                                    personalityForm.errors
                                                        .personality_type
                                                }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                :disabled="
                                                    personalityForm.processing
                                                "
                                                class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white shadow-xs hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                                                @click="savePersonalityType"
                                            >
                                                Save
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-md px-3 py-1.5 text-sm font-medium text-blue-700 hover:bg-blue-100"
                                                @click="
                                                    showPersonalityForm = false
                                                "
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="mb-6 max-w-4xl text-gray-600">
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

                <form class="max-w-4xl space-y-6" @submit.prevent="submit">
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
