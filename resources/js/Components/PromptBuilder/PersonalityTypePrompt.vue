<script setup lang="ts">
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonText from '@/Components/ButtonText.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import LinkText from '@/Components/LinkText.vue';
import UpdatePersonalityTypeForm from '@/Pages/Profile/Partials/UpdatePersonalityTypeForm.vue';
import { ref, watch } from 'vue';

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
}>();

const showPersonalityForm = ref(false);
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

// Watch for hasPersonalityType changes and reset form visibility
// This ensures the form is hidden when transitioning from "no personality" to "has personality"
watch(
    () => props.hasPersonalityType,
    (newValue, oldValue) => {
        // When personality type is added (false -> true), hide the form
        if (oldValue === false && newValue === true) {
            showPersonalityForm.value = false;
        }
    },
);
</script>

<template>
    <!-- Info message if no personality type -->
    <div
        v-if="!hasPersonalityType"
        key="no-personality"
        class="mb-6 rounded-md border border-amber-200 bg-amber-50 p-4"
    >
        <div class="flex">
            <div class="shrink-0">
                <DynamicIcon
                    name="information-circle"
                    class="h-5 w-5 text-amber-400"
                />
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-amber-800">
                    Get personalised prompts (optional)
                </h3>
                <div class="mt-2 text-sm text-amber-700">
                    <!-- Authenticated user -->
                    <p v-if="isAuthenticated">
                        For personalised prompts tailored to your communication
                        style,
                        <LinkText
                            ref="addPersonalityLinkRef"
                            :href="route('profile.edit')"
                        >
                            add your personality type
                        </LinkText>
                        to your profile. Otherwise, we'll select the best
                        framework based purely on your task.
                    </p>
                    <!-- Visitor -->
                    <div v-else>
                        <p class="mb-2">
                            For personalised prompts tailored to your
                            communication style, add your
                            <a
                                class="underline underline-offset-2"
                                href="https://16personalities.com"
                                target="_blank"
                                rel="noopener noreferrer"
                                >16personalities.com</a
                            >
                            type. Otherwise, we'll select the best framework
                            based purely on your task.
                        </p>
                        <ButtonText
                            v-if="!showPersonalityForm"
                            id="add-personality-type"
                            ref="addPersonalityButtonRef"
                            type="button"
                            variant="warning"
                            class="-m-1"
                            @click="showPersonalityForm = true"
                        >
                            Add personality type
                        </ButtonText>
                        <div v-else class="mt-3">
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
    </div>

    <!-- Display visitor personality type if set -->
    <div
        v-else-if="!isAuthenticated && visitorPersonalityType"
        key="has-personality"
        class="mb-6 rounded-md border border-amber-200 bg-amber-50 p-4"
    >
        <div class="flex items-start justify-between">
            <div class="flex flex-1 items-center">
                <div class="flex-1">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-sm font-medium text-amber-800">
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
