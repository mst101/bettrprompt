<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import VisitorLimitBanner from '@/Components/Common/VisitorLimitBanner.vue';
import PersonalityTypePrompt from '@/Components/Features/PromptBuilder/Forms/PersonalityTypePrompt.vue';
import TaskDescriptionForm from '@/Components/Features/PromptBuilder/Forms/TaskDescriptionForm.vue';
import { usePersonalityPromptPreference } from '@/Composables/features/usePersonalityPromptPreference';
import { useTextAppend } from '@/Composables/features/useTextAppend';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    computed,
    inject,
    nextTick,
    onMounted,
    onUnmounted,
    ref,
    watch,
} from 'vue';

interface Props {
    visitorPersonalityType?: string | null;
    visitorTraitPercentages?: Record<string, number> | null;
    personalityTypes: Record<string, string>;
    visitorHasCompletedPrompts?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    visitorPersonalityType: null,
    visitorTraitPercentages: null,
    visitorHasCompletedPrompts: false,
});

defineOptions({
    layout: AppLayout,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const openRegisterModal = inject<() => void>('openRegisterModal');
const openLoginModal = inject<() => void>('openLoginModal');
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
    form.post(route('prompt-builder.pre-analyse'), {
        onError: () => {
            submissionError.value =
                'Failed to submit prompt. Please check your connection and try again.';
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
</script>

<template>
    <Head title="Prompt Builder" />

    <HeaderPage title="Prompt Builder" />

    <ContainerPage>
        <Card>
            <!-- Error Alert -->
            <div
                v-if="submissionError"
                class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700"
                role="alert"
            >
                <p class="font-medium">Error submitting prompt</p>
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
                @submit="submit"
                @transcription="handleTranscription"
                @clear="clearTaskDescription"
                @update:task-description="
                    (value) => (form.taskDescription = value)
                "
            />
        </Card>
    </ContainerPage>
</template>
