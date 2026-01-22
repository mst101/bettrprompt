<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import UpgradePromptModal from '@/Components/Common/UpgradePromptModal.vue';
import VisitorLimitBanner from '@/Components/Common/VisitorLimitBanner.vue';
import LocationPromptModal from '@/Components/Features/PromptBuilder/Forms/LocationPromptModal.vue';
import PersonalityTypePrompt from '@/Components/Features/PromptBuilder/Forms/PersonalityTypePrompt.vue';
import TaskDescriptionForm from '@/Components/Features/PromptBuilder/Forms/TaskDescriptionForm.vue';
import { usePersonalityPromptPreference } from '@/Composables/features/usePersonalityPromptPreference';
import { useTextAppend } from '@/Composables/features/useTextAppend';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    computed,
    inject,
    nextTick,
    onMounted,
    onUnmounted,
    ref,
    watch,
} from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    visitorPersonalityType?: string | null;
    visitorTraitPercentages?: Record<string, number> | null;
    personalityTypes: Record<string, string>;
    visitorHasCompletedPrompts?: boolean;
    locationData: LocationData;
    countries: SelectOption[];
    currencies: SelectOption[];
    languages: SelectOption[];
    locationPromptDismissed?: boolean;
}

interface LocationData {
    countryCode: string | null;
    countryName: string | null;
    region: string | null;
    city: string | null;
    timezone: string | null;
    currencyCode: string | null;
    languageCode: string | null;
    detectedAt: string | null;
    manuallySet: boolean;
}

interface SelectOption {
    value: string;
    label: string;
}

const props = withDefaults(defineProps<Props>(), {
    visitorPersonalityType: null,
    visitorTraitPercentages: null,
    visitorHasCompletedPrompts: false,
    locationPromptDismissed: false,
});

defineOptions({
    layout: AppLayout,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
// Get subscription from user resource (includes full subscription data)
const subscription = computed(() => {
    return user.value?.subscription || page.props.subscription || {};
});
const openRegisterModal = inject<() => void>('openRegisterModal');
const openLoginModal = inject<() => void>('openLoginModal');
const { t } = useI18n({ useScope: 'global' });
const { countryRoute, currentCountry } = useCountryRoute();
const locationData = computed(() => props.locationData);
const countries = computed(() => props.countries);
const currencies = computed(() => props.currencies);
const languages = computed(() => props.languages);
const hasPersonalityType = computed(() => {
    // Authenticated users check their user profile
    if (user.value) {
        return !!user.value?.personalityType;
    }
    // Visitors check props passed from controller
    return !!props.visitorPersonalityType;
});

const showLowPromptsWarning = computed(() => {
    return (
        subscription.value.isFree &&
        subscription.value.promptsRemaining > 0 &&
        subscription.value.promptsRemaining <= 2
    );
});

const taskDescriptionFormRef = ref<InstanceType<
    typeof TaskDescriptionForm
> | null>(null);
const personalityTypePromptRef = ref<InstanceType<
    typeof PersonalityTypePrompt
> | null>(null);

const form = useForm({
    taskDescription: '',
    personalityType:
        user.value?.personalityType || props.visitorPersonalityType || '',
    traitPercentages:
        user.value?.traitPercentages || props.visitorTraitPercentages || null,
});

const submissionError = ref<string | null>(null);
const showUpgradeModal = ref(false);
const limitErrorData = ref<{
    promptsUsed: number;
    promptLimit: number;
    daysUntilReset: number;
} | null>(null);

const submit = () => {
    submissionError.value = null;
    form.post(countryRoute('prompt-builder.pre-analyse'), {
        onError: (errors) => {
            // Check if it's a 403 prompt limit error
            if (errors && 'promptLimit' in errors && 'promptsUsed' in errors) {
                limitErrorData.value = {
                    promptsUsed: errors.promptsUsed as number,
                    promptLimit: errors.promptLimit as number,
                    daysUntilReset: (errors.daysUntilReset as number) || 0,
                };
                showUpgradeModal.value = true;
            } else {
                submissionError.value = t('promptBuilder.errors.submitFailed');
            }
        },
    });
};

const { appendText } = useTextAppend();

const handleTranscription = (text: string) => {
    form.taskDescription = appendText(form.taskDescription, text);
};

const clearTaskDescription = () => {
    form.taskDescription = '';
};

// Handle personality form save - focus task description textarea
const handlePersonalitySaved = async () => {
    await nextTick();
    taskDescriptionFormRef.value?.focus();
};

// Handle focus task description event from personality prompt
const handleFocusTaskDescription = async () => {
    await nextTick();
    taskDescriptionFormRef.value?.focus();
};

const { isDismissed } = usePersonalityPromptPreference();

const showLocationPrompt = ref(false);
const hasShownLocationPrompt = ref(false);
const localPromptDismissed = ref(false);

const missingLocationData = computed(() => {
    const data = locationData.value;
    return (
        !data.countryCode ||
        !data.languageCode ||
        !data.currencyCode ||
        !data.timezone
    );
});

const isCountryMismatch = computed(() => {
    const locationCountry = locationData.value.countryCode;
    if (!locationCountry) {
        return false;
    }

    return locationCountry.toLowerCase() !== currentCountry.value.toLowerCase();
});

const isLocationPromptDismissed = computed(() => {
    return user.value
        ? !!props.locationPromptDismissed
        : localPromptDismissed.value;
});

const shouldPromptForLocation = computed(() => {
    if (hasShownLocationPrompt.value || isLocationPromptDismissed.value) {
        return false;
    }

    return missingLocationData.value || isCountryMismatch.value;
});

const persistLocationPromptDismissal = async (dismissed: boolean) => {
    if (!dismissed) {
        return;
    }

    if (user.value) {
        try {
            await axios.patch(
                countryRoute('profile.location.prompt'),
                { dismissed: true },
                { headers: { Accept: 'application/json' } },
            );
        } catch (error) {
            console.error('Failed to store location prompt preference', error);
        }

        return;
    }

    localPromptDismissed.value = true;
    localStorage.setItem('location_prompt_dismissed', 'true');
};

const handleLocationContinue = async ({
    dontAskAgain,
}: {
    dontAskAgain: boolean;
}) => {
    await persistLocationPromptDismissal(dontAskAgain);
    showLocationPrompt.value = false;
    hasShownLocationPrompt.value = true;
    submit();
};

const getCountryLabel = (code: string | null) => {
    if (!code) {
        return '';
    }
    const match = props.countries.find(
        (country) => country.value.toLowerCase() === code.toLowerCase(),
    );
    return match?.label || code.toUpperCase();
};

const locationPromptReason = computed(() => {
    if (isCountryMismatch.value) {
        return t('promptBuilder.locationPrompt.reason.mismatch', {
            currentCountry: getCountryLabel(currentCountry.value),
            savedCountry: getCountryLabel(locationData.value.countryCode),
        });
    }
    if (missingLocationData.value) {
        return t('promptBuilder.locationPrompt.reason.missing');
    }
    return '';
});

const handleLocationUpdated = async ({
    dontAskAgain,
    countryCode,
}: {
    dontAskAgain: boolean;
    countryCode: string;
}) => {
    await persistLocationPromptDismissal(dontAskAgain);
    showLocationPrompt.value = false;
    hasShownLocationPrompt.value = true;
    const nextCountry = countryCode?.toLowerCase();

    if (nextCountry && nextCountry !== currentCountry.value.toLowerCase()) {
        router.visit(`/${nextCountry}/prompt-builder`, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                submit();
            },
        });
        return;
    }

    submit();
};

const focusAppropriateElement = async () => {
    await nextTick();
    // Use requestAnimationFrame to wait for all rendering to complete
    requestAnimationFrame(() => {
        setTimeout(() => {
            // On sm: and wider, if personality prompt is dismissed, focus textarea
            const isSmallScreenOrWider =
                typeof window !== 'undefined' && window.innerWidth >= 640;
            if (isSmallScreenOrWider && isDismissed.value) {
                taskDescriptionFormRef.value?.focus();
            } else if (!hasPersonalityType.value) {
                // Focus the personality prompt (link or button)
                personalityTypePromptRef.value?.focus();
            } else if (isSmallScreenOrWider) {
                // Only focus textarea on larger screens when personality type is set
                taskDescriptionFormRef.value?.focus();
            }
        }, 200);
    });
};

// Focus appropriate element on mount
onMounted(() => {
    focusAppropriateElement();

    if (!user.value) {
        localPromptDismissed.value =
            localStorage.getItem('location_prompt_dismissed') === 'true';
    }
});

// Also focus on Inertia navigation finish (for login redirects)
const finishHandler = () => {
    focusAppropriateElement();
};

let removeFinishListener: (() => void) | null = null;

onMounted(() => {
    removeFinishListener = router.on('finish', finishHandler);
});

onUnmounted(() => {
    if (removeFinishListener) {
        removeFinishListener();
    }
});

// Watch for visitor personality type changes (first-time save)
// Focus textarea when personality type is set for first time
watch(
    () => props.visitorPersonalityType,
    async (newValue, oldValue) => {
        if (oldValue === null && newValue !== null) {
            await nextTick();
            taskDescriptionFormRef.value?.focus();
        }
    },
);

const handleSubmit = () => {
    submissionError.value = null;
    if (shouldPromptForLocation.value) {
        showLocationPrompt.value = true;
        return;
    }

    submit();
};
</script>

<template>
    <Head :title="$t('promptBuilder.title')" />

    <HeaderPage :title="$t('promptBuilder.title')" />

    <ContainerPage>
        <Card class="max-w-4xl">
            <!-- Error Alert -->
            <div
                v-if="submissionError"
                class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700"
                role="alert"
            >
                <p class="font-medium">
                    {{ $t('promptBuilder.errors.submitTitle') }}
                </p>
                <p>{{ submissionError }}</p>
            </div>

            <PersonalityTypePrompt
                v-if="!hasPersonalityType"
                ref="personalityTypePromptRef"
                :has-personality-type="hasPersonalityType"
                :is-authenticated="!!user"
                :visitor-personality-type="visitorPersonalityType"
                :visitor-trait-percentages="visitorTraitPercentages"
                :personality-types="personalityTypes"
                @saved="handlePersonalitySaved"
                @focus-task-description="handleFocusTaskDescription"
            />

            <VisitorLimitBanner
                v-if="!user && visitorHasCompletedPrompts"
                variant="inline"
                @register="openRegisterModal"
                @login="openLoginModal"
            />

            <!-- Low Prompts Warning Banner -->
            <div
                v-if="showLowPromptsWarning"
                class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4"
                data-testid="low-prompts-warning"
            >
                <div class="flex items-start">
                    <svg
                        class="mt-0.5 h-5 w-5 text-amber-600"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-amber-900">
                            {{
                                $tc(
                                    'promptBuilder.lowPromptsWarning.title',
                                    subscription.promptsRemaining,
                                    {
                                        count: subscription.promptsRemaining,
                                    },
                                )
                            }}
                        </h3>
                        <p class="mt-1 text-sm text-amber-700">
                            {{
                                $tc(
                                    'promptBuilder.lowPromptsWarning.description',
                                    subscription.daysUntilReset,
                                    {
                                        days: subscription.daysUntilReset,
                                    },
                                )
                            }}
                        </p>
                        <div class="mt-3">
                            <a
                                :href="countryRoute('pricing')"
                                class="text-sm font-medium text-amber-900 underline hover:text-amber-800"
                            >
                                {{
                                    $t(
                                        'promptBuilder.lowPromptsWarning.upgradeLink',
                                    )
                                }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <TaskDescriptionForm
                v-else
                ref="taskDescriptionFormRef"
                :has-personality-type="hasPersonalityType"
                :form="form"
                @submit="handleSubmit"
                @transcription="handleTranscription"
                @clear="clearTaskDescription"
                @update:task-description="
                    (value) => (form.taskDescription = value)
                "
            />
        </Card>

        <LocationPromptModal
            :show="showLocationPrompt"
            :location-data="locationData"
            :countries="countries"
            :currencies="currencies"
            :languages="languages"
            :reason-text="locationPromptReason"
            :is-authenticated="!!user"
            @close="showLocationPrompt = false"
            @continue="handleLocationContinue"
            @updated="handleLocationUpdated"
        />

        <UpgradePromptModal
            :show="showUpgradeModal"
            @close="showUpgradeModal = false"
        />
    </ContainerPage>
</template>
