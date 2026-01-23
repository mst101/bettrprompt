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
import { useI18n } from 'vue-i18n';

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
const addPersonalityButtonRef = ref<InstanceType<typeof ButtonText> | null>(
    null,
);
const { t } = useI18n({ useScope: 'global' });

const focus = () => {
    // Only focus on larger screens (sm breakpoint and above: 640px)
    if (!window.matchMedia('(min-width: 640px)').matches) {
        return;
    }

    addPersonalityButtonRef.value?.focus();
};

defineExpose({ focus });

const handlePersonalitySaved = () => {
    showPersonalityForm.value = false;
    emit('saved');
};

const handleMaybeLater = () => {
    dismissPrompt();
    addNotification({
        message: t(
            'promptBuilder.components.personalityTypePrompt.maybeLaterNotification',
        ),
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
                            aria-label="Learn about your personality type on 16personalities.com"
                        >
                            <DynamicIcon
                                name="personalities"
                                class="my-2 h-16 w-full rounded-lg text-indigo-600 hover:bg-indigo-100 sm:h-fit sm:w-80 sm:p-4"
                            />
                        </a>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-indigo-900">
                            {{
                                $t(
                                    'promptBuilder.components.personalityTypePrompt.title',
                                )
                            }}
                        </h2>
                        <p class="mt-2 text-sm">
                            <i18n-t
                                keypath="promptBuilder.components.personalityTypePrompt.description"
                                scope="global"
                                tag="span"
                            >
                                <template #link>
                                    <LinkText
                                        href="https://16personalities.com"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {{
                                            $t(
                                                'promptBuilder.components.personalityTypePrompt.linkText',
                                            )
                                        }}
                                    </LinkText>
                                </template>
                            </i18n-t>
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
                        {{
                            $t(
                                'promptBuilder.components.personalityTypePrompt.addButton',
                            )
                        }}
                    </ButtonPrimary>
                    <ButtonSecondary
                        id="maybe-later"
                        type="button"
                        class="w-full sm:w-fit"
                        @click="handleMaybeLater"
                    >
                        {{
                            $t(
                                'promptBuilder.components.personalityTypePrompt.maybeLater',
                            )
                        }}
                    </ButtonSecondary>
                </div>
                <div v-else class="mt-3">
                    <UpdatePersonalityTypeForm
                        :personality-types="personalityTypes"
                        :visitor-mode="true"
                        :visitor-personality-type="visitorPersonalityType"
                        :visitor-trait-percentages="visitorTraitPercentages"
                        :collapsible="false"
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
                            {{
                                $t(
                                    'promptBuilder.components.personalityTypePrompt.label',
                                )
                            }}
                            <span class="whitespace-nowrap">{{
                                visitorPersonalityType
                            }}</span>
                        </h3>
                        <ButtonSecondary
                            id="edit-personality-type"
                            type="button"
                            @click="showPersonalityForm = !showPersonalityForm"
                        >
                            {{
                                showPersonalityForm
                                    ? $t('common.buttons.cancel')
                                    : $t('common.buttons.edit')
                            }}
                        </ButtonSecondary>
                    </div>
                    <div v-if="showPersonalityForm" class="mt-2">
                        <UpdatePersonalityTypeForm
                            :personality-types="personalityTypes"
                            :visitor-mode="true"
                            :visitor-personality-type="visitorPersonalityType"
                            :visitor-trait-percentages="visitorTraitPercentages"
                            :collapsible="false"
                            @saved="handlePersonalitySaved"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
