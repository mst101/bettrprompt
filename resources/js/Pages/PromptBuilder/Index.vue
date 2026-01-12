<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
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

const submit = () => {
    submissionError.value = null;
    form.post(countryRoute('prompt-builder.pre-analyse'), {
        onError: () => {
            submissionError.value = t('promptBuilder.errors.submitFailed');
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
        <Card>
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
    </ContainerPage>
</template>
