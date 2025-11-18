<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonText from '@/Components/ButtonText.vue';
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import ContainerPage from '@/Components/ContainerPage.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import FormTextareaWithActions from '@/Components/FormTextareaWithActions.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import LinkText from '@/Components/LinkText.vue';
import ButtonTrash from '@/Components/PromptOptimizer/ButtonTrash.vue';
import { useTextAppend } from '@/Composables/useTextAppend';
import AppLayout from '@/Layouts/AppLayout.vue';
import UpdatePersonalityTypeForm from '@/Pages/Profile/Partials/UpdatePersonalityTypeForm.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, inject, nextTick, ref, watch } from 'vue';

interface Props {
    visitorPersonalityType?: string | null;
    visitorTraitPercentages?: Record<string, number> | null;
    personalityTypes: Record<string, string>;
    visitorHasCompletedPrompts?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    visitorHasCompletedPrompts: false,
});

defineOptions({
    layout: AppLayout,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const openRegisterModal = inject<() => void>('openRegisterModal');
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
const taskDescriptionTextarea = ref<InstanceType<
    typeof FormTextareaWithActions
> | null>(null);

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

// Handle personality form save
const handlePersonalitySaved = async () => {
    showPersonalityForm.value = false;
    // Focus the task description textarea after closing the form
    await nextTick();
    taskDescriptionTextarea.value?.focus();
};

// Watch for visitor personality type changes and close form
// This handles the case when a visitor submits for the first time
watch(
    () => props.visitorPersonalityType,
    async (newValue, oldValue) => {
        // If personality type changes from null to a value, close the form and focus textarea
        if (oldValue === null && newValue !== null) {
            showPersonalityForm.value = false;
            await nextTick();
            taskDescriptionTextarea.value?.focus();
        }
    },
);
</script>

<template>
    <Head title="Prompt Optimiser" />

    <HeaderPage title="Prompt Optimiser" />

    <ContainerPage>
        <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
            <div class="max-w-4xl px-6 sm:p-6">
                <!-- Info message if no personality type -->
                <div
                    v-if="!hasPersonalityType"
                    class="mb-6 rounded-md border border-indigo-200 bg-indigo-50 p-4"
                >
                    <div class="flex">
                        <div class="shrink-0">
                            <DynamicIcon
                                name="information-circle"
                                class="h-5 w-5 text-indigo-400"
                            />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-indigo-800">
                                Get personalised prompts (optional)
                            </h3>
                            <div class="mt-2 text-sm text-indigo-700">
                                <!-- Authenticated user -->
                                <p v-if="user">
                                    For personalised prompts tailored to your
                                    communication style,
                                    <LinkText :href="route('profile.edit')">
                                        add your personality type
                                    </LinkText>
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
                                    <ButtonText
                                        v-if="!showPersonalityForm"
                                        id="add-personality-type"
                                        type="button"
                                        class="-m-1"
                                        @click="showPersonalityForm = true"
                                    >
                                        Add personality type
                                    </ButtonText>
                                    <div v-else class="mt-3">
                                        <UpdatePersonalityTypeForm
                                            :personality-types="
                                                personalityTypes
                                            "
                                            :visitor-mode="true"
                                            :visitor-personality-type="
                                                visitorPersonalityType
                                            "
                                            :visitor-trait-percentages="
                                                visitorTraitPercentages
                                            "
                                            @saved="handlePersonalitySaved"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Display visitor personality type if set -->
                <div
                    v-else-if="!user && visitorPersonalityType"
                    class="mb-6 rounded-md border border-indigo-200 bg-indigo-50 p-4"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex flex-1 items-center">
                            <div class="flex-1">
                                <div
                                    class="flex items-center justify-between gap-4"
                                >
                                    <h3
                                        class="text-sm font-medium text-indigo-800"
                                    >
                                        Personality Type:
                                        <span class="whitespace-nowrap">{{
                                            visitorPersonalityType
                                        }}</span>
                                    </h3>
                                    <ButtonSecondary
                                        id="edit-personality-type"
                                        type="button"
                                        @click="
                                            showPersonalityForm =
                                                !showPersonalityForm
                                        "
                                    >
                                        {{
                                            showPersonalityForm
                                                ? 'Cancel'
                                                : 'Edit'
                                        }}
                                    </ButtonSecondary>
                                </div>
                                <div v-if="showPersonalityForm" class="mt-2">
                                    <UpdatePersonalityTypeForm
                                        :personality-types="personalityTypes"
                                        :visitor-mode="true"
                                        :visitor-personality-type="
                                            visitorPersonalityType
                                        "
                                        :visitor-trait-percentages="
                                            visitorTraitPercentages
                                        "
                                        @saved="handlePersonalitySaved"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visitor limit message -->
                <div
                    v-if="!user && visitorHasCompletedPrompts"
                    class="mb-6 rounded-lg border-2 border-indigo-200 bg-indigo-50 p-6"
                >
                    <div class="flex items-start gap-3">
                        <DynamicIcon
                            name="information-circle"
                            class="mt-0.5 h-6 w-6 shrink-0 text-indigo-600"
                        />
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-indigo-900">
                                You've reached your visitor limit
                            </h3>
                            <p class="mt-2 text-gray-700">
                                You've already created an optimised prompt as a
                                visitor. To create more prompts, save your work,
                                and iterate on existing ones, you'll need to
                                create a free account.
                            </p>
                            <div class="mt-4 flex gap-3">
                                <ButtonPrimary @click="openRegisterModal?.()">
                                    Create Free Account
                                </ButtonPrimary>
                                <ButtonSecondary
                                    :href="route('prompt-optimizer.history')"
                                >
                                    View History
                                </ButtonSecondary>
                            </div>
                        </div>
                    </div>
                </div>

                <template v-else>
                    <p class="mb-6 max-w-4xl text-gray-600">
                        Create optimised AI prompts using expert frameworks.
                        <span v-if="hasPersonalityType">
                            Prompts will be customised to your personality type
                            and task requirements.
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
                            ref="taskDescriptionTextarea"
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
                                <ButtonTrash
                                    v-if="form.taskDescription"
                                    @click="clearTaskDescription"
                                />
                                <ButtonVoiceInput
                                    @transcription="handleTranscription"
                                />
                            </template>
                        </FormTextareaWithActions>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end">
                            <ButtonPrimary
                                type="submit"
                                :disabled="form.processing || !hasTask"
                            >
                                <span v-if="form.processing"
                                    >Processing...</span
                                >
                                <span v-else>Optimise Prompt</span>
                            </ButtonPrimary>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </ContainerPage>
</template>
