<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import ButtonText from '@/Components/Base/Button/ButtonText.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import LinkText from '@/Components/Base/LinkText.vue';
import { usePersonalityPromptPreference } from '@/Composables/features/usePersonalityPromptPreference';
import { useNotification } from '@/Composables/ui/useNotification';
import UpdatePersonalityTypeForm from '@/Pages/Profile/Partials/UpdatePersonalityTypeForm.vue';
import { computed, ref, watch } from 'vue';

interface Props {
    hasPersonalityType: boolean;
    isAuthenticated: boolean;
    visitorPersonalityType?: string | null;
    visitorTraitPercentages?: Record<string, number> | null;
    personalityTypes: Record<string, string>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'saved'): void;
    (e: 'focus-task-description'): void;
}>();

const { add: addNotification } = useNotification();
const { dismissPrompt, showPrompt } = usePersonalityPromptPreference();
const showPersonalityForm = ref(false);
const showPersonalityBox = computed(() => showPrompt.value);
const addPersonalityLinkRef = ref<InstanceType<typeof LinkText> | null>(null);
const addPersonalityButtonRef = ref<InstanceType<typeof ButtonText> | null>(
    null,
);

const focus = () => {
    // Focus the link if authenticated, button if visitor
    if (props.isAuthenticated) {
        addPersonalityLinkRef.value?.focus();
    } else {
        addPersonalityButtonRef.value?.focus();
    }
};

defineExpose({ focus });

const handlePersonalitySaved = () => {
    showPersonalityForm.value = false;
    emit('saved');
};

const handleMaybeLater = () => {
    dismissPrompt();
    addNotification({
        message:
            'You can always add your personality type later in the Profile section of your account.',
        type: 'info',
        autoDismiss: true,
        dismissDelay: 5000,
    });
    emit('focus-task-description');
};

// Watch for hasPersonalityType changes and reset form visibility
// This ensures the form is hidden when transitioning from "no personality" to "has personality"
watch(
    () => props.hasPersonalityType,
    (newValue, oldValue) => {
        // When personality type is added (false -> true), hide the form
        if (!oldValue && newValue) {
            showPersonalityForm.value = false;
        }
    },
);
</script>

<template>
    <!-- Info message if no personality type -->
    <div
        v-if="!hasPersonalityType && showPersonalityBox"
        key="no-personality"
        class="mb-6 rounded-md border border-indigo-200 bg-indigo-50 p-4"
    >
        <div class="flex flex-col">
            <div class="mt-2 text-indigo-700">
                <div class="flex flex-col items-start gap-4 sm:flex-row">
                    <div class="sm:-ml-4">
                        <a
                            class="block rounded-lg outline-none focus:ring-2 focus:ring-indigo-500"
                            href="https://16personalities.com"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <DynamicIcon
                                name="personalities"
                                class="my-2 h-16 w-full rounded-lg text-indigo-600 hover:bg-indigo-100 sm:h-fit sm:w-80 sm:p-4"
                            />
                        </a>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-indigo-900">
                            Get personalised prompts (optional)
                        </h3>
                        <p class="mt-2 text-sm">
                            For personalised prompts tailored to your way of
                            thinking and communication style, add your
                            <LinkText
                                href="https://16personalities.com"
                                target="_blank"
                                rel="noopener noreferrer"
                                >16personalities.com</LinkText
                            >
                            type. Otherwise, we'll select the best framework and
                            questions based purely on your task.
                        </p>
                    </div>
                </div>
                <div
                    v-if="!showPersonalityForm"
                    class="mt-4 flex flex-col gap-4 sm:flex-row sm:justify-end"
                >
                    <ButtonPrimary
                        id="add-personality-type"
                        ref="addPersonalityButtonRef"
                        type="button"
                        class="w-full sm:w-fit"
                        @click="showPersonalityForm = true"
                    >
                        Add personality type
                    </ButtonPrimary>
                    <ButtonSecondary
                        id="maybe-later"
                        type="button"
                        class="w-full sm:w-fit"
                        @click="handleMaybeLater"
                    >
                        Maybe later
                    </ButtonSecondary>
                </div>
                <div v-else class="mt-3">
                    <UpdatePersonalityTypeForm
                        :personality-types="personalityTypes"
                        :visitor-mode="true"
                        :visitor-personality-type="visitorPersonalityType"
                        :visitor-trait-percentages="visitorTraitPercentages"
                        @saved="handlePersonalitySaved"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Display visitor personality type if set -->
    <div
        v-else-if="!isAuthenticated && visitorPersonalityType"
        key="has-personality"
        class="mb-6 rounded-md border border-indigo-200 bg-indigo-50 p-4 dark:bg-indigo-100"
    >
        <div class="flex items-start justify-between">
            <div class="flex flex-1 items-center">
                <div class="flex-1">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-sm font-medium text-indigo-800">
                            Personality Type:
                            <span class="whitespace-nowrap">{{
                                visitorPersonalityType
                            }}</span>
                        </h3>
                        <ButtonSecondary
                            id="edit-personality-type"
                            type="button"
                            @click="showPersonalityForm = !showPersonalityForm"
                        >
                            {{ showPersonalityForm ? 'Cancel' : 'Edit' }}
                        </ButtonSecondary>
                    </div>
                    <div v-if="showPersonalityForm" class="mt-2">
                        <UpdatePersonalityTypeForm
                            :personality-types="personalityTypes"
                            :visitor-mode="true"
                            :visitor-personality-type="visitorPersonalityType"
                            :visitor-trait-percentages="visitorTraitPercentages"
                            @saved="handlePersonalitySaved"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
