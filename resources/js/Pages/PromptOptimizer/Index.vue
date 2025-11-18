<script setup lang="ts">
import ContainerPage from '@/Components/ContainerPage.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import PersonalityTypePrompt from '@/Components/PromptOptimizer/PersonalityTypePrompt.vue';
import TaskDescriptionForm from '@/Components/PromptOptimizer/TaskDescriptionForm.vue';
import VisitorLimitBanner from '@/Components/PromptOptimizer/VisitorLimitBanner.vue';
import { useTextAppend } from '@/Composables/useTextAppend';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, inject, nextTick, ref, watch } from 'vue';

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

// Handle personality form save - focus task description textarea
const handlePersonalitySaved = async () => {
    await nextTick();
    taskDescriptionFormRef.value?.focus();
};

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
    <Head title="Prompt Optimiser" />

    <HeaderPage title="Prompt Optimiser" />

    <ContainerPage>
        <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
            <div class="max-w-4xl px-6 sm:p-6">
                <PersonalityTypePrompt
                    :has-personality-type="hasPersonalityType"
                    :is-authenticated="!!user"
                    :visitor-personality-type="visitorPersonalityType"
                    :visitor-trait-percentages="visitorTraitPercentages"
                    :personality-types="personalityTypes"
                    @saved="handlePersonalitySaved"
                />

                <VisitorLimitBanner
                    v-if="!user && visitorHasCompletedPrompts"
                    @register="openRegisterModal"
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
            </div>
        </div>
    </ContainerPage>
</template>
