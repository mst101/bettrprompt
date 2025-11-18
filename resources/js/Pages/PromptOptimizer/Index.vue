<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonText from '@/Components/ButtonText.vue';
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import ContainerPage from '@/Components/ContainerPage.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import FormInput from '@/Components/FormInput.vue';
import FormTextareaWithActions from '@/Components/FormTextareaWithActions.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import LinkText from '@/Components/LinkText.vue';
import ButtonTrash from '@/Components/PromptOptimizer/ButtonTrash.vue';
import { useTextAppend } from '@/Composables/useTextAppend';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
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
                                        variant="info"
                                        @click="showPersonalityForm = true"
                                    >
                                        Add personality type
                                    </ButtonText>
                                    <div v-else class="mt-3 space-y-3">
                                        <div>
                                            <FormInput
                                                id="visitor-personality-type"
                                                v-model="
                                                    personalityForm.personality_type
                                                "
                                                class="max-w-xs text-blue-900"
                                                label="Personality Type (e.g., INTJ-T)"
                                                type="text"
                                                placeholder="XXXX-X"
                                                maxlength="6"
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
                                            <ButtonPrimary
                                                type="button"
                                                :disabled="
                                                    personalityForm.processing
                                                "
                                                @click="savePersonalityType"
                                            >
                                                Save
                                            </ButtonPrimary>
                                            <ButtonSecondary
                                                type="button"
                                                @click="
                                                    showPersonalityForm = false
                                                "
                                            >
                                                Cancel
                                            </ButtonSecondary>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Display visitor personality type if set -->
                <div
                    v-else-if="!user && visitorPersonalityType"
                    class="mb-6 rounded-md border border-green-200 bg-green-50 p-4"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex">
                            <div class="shrink-0">
                                <DynamicIcon
                                    name="check-circle"
                                    class="h-5 w-5 text-green-400"
                                />
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">
                                    Personality Type Set
                                </h3>
                                <div
                                    v-if="!showPersonalityForm"
                                    class="mt-2 text-sm text-green-700"
                                >
                                    <p class="mb-1">
                                        Your personality type:
                                        <strong class="font-semibold">{{
                                            visitorPersonalityType
                                        }}</strong>
                                    </p>
                                    <ButtonText
                                        id="edit-personality-type"
                                        type="button"
                                        variant="success"
                                        @click="showPersonalityForm = true"
                                    >
                                        Edit personality type
                                    </ButtonText>
                                </div>
                                <div v-else class="mt-3 space-y-3">
                                    <div>
                                        <FormInput
                                            id="visitor-personality-type-edit"
                                            v-model="
                                                personalityForm.personality_type
                                            "
                                            class="max-w-xs text-green-900"
                                            label="Personality Type (e.g., INTJ-T)"
                                            type="text"
                                            placeholder="XXXX-X"
                                            maxlength="6"
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
                                        <ButtonPrimary
                                            type="button"
                                            :disabled="
                                                personalityForm.processing
                                            "
                                            @click="savePersonalityType"
                                        >
                                            Save
                                        </ButtonPrimary>
                                        <ButtonSecondary
                                            type="button"
                                            @click="
                                                showPersonalityForm = false;
                                                personalityForm.personality_type =
                                                    visitorPersonalityType ||
                                                    '';
                                            "
                                        >
                                            Cancel
                                        </ButtonSecondary>
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
                            <span v-if="form.processing">Processing...</span>
                            <span v-else>Optimise Prompt</span>
                        </ButtonPrimary>
                    </div>
                </form>
            </div>
        </div>
    </ContainerPage>
</template>
