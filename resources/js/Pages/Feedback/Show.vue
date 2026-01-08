<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import Card from '@/Components/Base/Card.vue';
import FormCheckboxGroup from '@/Components/Base/Form/FormCheckboxGroup.vue';
import FormTextarea from '@/Components/Base/Form/FormTextarea.vue';
import LikertScale from '@/Components/Base/LikertScale.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

interface FeedbackData {
    experienceLevel: number;
    usefulness: number;
    usageIntent: number;
    suggestions: string | null;
    desiredFeatures: string[];
    desiredFeaturesOther: string | null;
    createdAt: string;
    updatedAt: string;
}

interface Props {
    feedback: FeedbackData;
}

const isEditing = ref(false);

const { t } = useI18n();

const form = useForm({
    experienceLevel: props.feedback.experienceLevel,
    usefulness: props.feedback.usefulness,
    usageIntent: props.feedback.usageIntent,
    suggestions: props.feedback.suggestions || '',
    desiredFeatures: props.feedback.desiredFeatures || [],
    desiredFeaturesOther: props.feedback.desiredFeaturesOther || '',
});

const featureOptions = computed(() => [
    {
        value: 'templates',
        label: t('feedback.features.templates.label'),
        description: t('feedback.features.templates.description'),
    },
    {
        value: 'compare',
        label: t('feedback.features.compare.label'),
        description: t('feedback.features.compare.description'),
    },
    {
        value: 'api-integration',
        label: t('feedback.features.apiIntegration.label'),
        description: t('feedback.features.apiIntegration.description'),
    },
    {
        value: 'collaboration',
        label: t('feedback.features.collaboration.label'),
        description: t('feedback.features.collaboration.description'),
    },
    {
        value: 'model-specific',
        label: t('feedback.features.modelSpecific.label'),
        description: t('feedback.features.modelSpecific.description'),
    },
    {
        value: 'document-upload',
        label: t('feedback.features.documentUpload.label'),
        description: t('feedback.features.documentUpload.description'),
    },
    {
        value: 'other',
        label: t('feedback.features.other.label'),
        description: t('feedback.features.other.description'),
    },
]);

const submit = () => {
    form.put(route('feedback.update'), {
        onSuccess: () => {
            isEditing.value = false;
        },
    });
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head :title="$t('feedback.show.title')" />

    <HeaderPage :title="$t('feedback.show.heading')" />

    <ContainerPage>
        <Card>
            <div class="mb-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-indigo-900">
                            {{ $t('feedback.show.thankYou') }}
                        </h2>
                        <p class="mt-1 text-sm text-indigo-600">
                            {{ $t('feedback.show.subtitle') }}
                        </p>
                    </div>
                    <ButtonSecondary
                        v-if="!isEditing"
                        type="button"
                        icon="edit"
                        @click="isEditing = true"
                    >
                        {{ $t('feedback.show.actions.edit') }}
                    </ButtonSecondary>
                </div>
                <p class="mt-2 text-xs text-indigo-500">
                    {{
                        $t('feedback.show.lastUpdated', {
                            date: formatDate(feedback.updatedAt),
                        })
                    }}
                </p>
            </div>

            <form class="space-y-8" @submit.prevent="submit">
                <!-- Question 1: Experience Level -->
                <div class="mt-8">
                    <label
                        class="mb-4 block text-sm font-medium text-indigo-900"
                    >
                        {{ $t('feedback.questions.experience.label') }}
                    </label>
                    <LikertScale
                        v-model="form.experienceLevel"
                        :left-label="$t('feedback.questions.experience.left')"
                        :right-label="$t('feedback.questions.experience.right')"
                        :disabled="!isEditing || form.processing"
                    />
                    <p
                        v-if="form.errors.experienceLevel"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.experienceLevel }}
                    </p>
                </div>

                <!-- Question 2: Usefulness -->
                <div class="mt-16">
                    <label
                        class="mb-4 block text-sm font-medium text-indigo-900"
                    >
                        {{ $t('feedback.questions.usefulness.label') }}
                    </label>
                    <LikertScale
                        v-model="form.usefulness"
                        :left-label="$t('feedback.questions.usefulness.left')"
                        :right-label="$t('feedback.questions.usefulness.right')"
                        :disabled="!isEditing || form.processing"
                    />
                    <p
                        v-if="form.errors.usefulness"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.usefulness }}
                    </p>
                </div>

                <!-- Question 3: Usage Intent -->
                <div class="mt-16">
                    <label
                        class="mb-4 block text-sm font-medium text-indigo-900"
                    >
                        {{ $t('feedback.questions.usageIntent.label') }}
                    </label>
                    <LikertScale
                        v-model="form.usageIntent"
                        :left-label="$t('feedback.questions.usageIntent.left')"
                        :right-label="
                            $t('feedback.questions.usageIntent.right')
                        "
                        :disabled="!isEditing || form.processing"
                    />
                    <p
                        v-if="form.errors.usageIntent"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.usageIntent }}
                    </p>
                </div>

                <!-- Question 4: Suggestions -->
                <div class="mt-16">
                    <FormTextarea
                        id="suggestions"
                        v-model="form.suggestions"
                        :label="$t('feedback.questions.suggestions.label')"
                        :error="form.errors.suggestions"
                        :disabled="!isEditing || form.processing"
                        :placeholder="
                            $t('feedback.questions.suggestions.placeholder')
                        "
                        :rows="5"
                    />
                </div>

                <!-- Question 5: Desired Features -->
                <div class="mt-16">
                    <label
                        class="mb-4 block text-sm font-medium text-indigo-900"
                    >
                        {{ $t('feedback.questions.features.label') }}
                        <span class="font-normal text-indigo-600">{{
                            $t('feedback.questions.features.hintAll')
                        }}</span>
                    </label>
                    <FormCheckboxGroup
                        v-model="form.desiredFeatures"
                        v-model:other-value="form.desiredFeaturesOther"
                        :options="featureOptions"
                        :disabled="!isEditing || form.processing"
                        :error="form.errors.desiredFeatures"
                    />
                </div>

                <!-- Submit Buttons -->
                <div
                    v-if="isEditing"
                    class="flex items-center justify-end gap-3"
                >
                    <ButtonSecondary
                        type="button"
                        :disabled="form.processing"
                        @click="
                            () => {
                                isEditing = false;
                                form.reset();
                                form.clearErrors();
                            }
                        "
                    >
                        {{ $t('common.buttons.cancel') }}
                    </ButtonSecondary>
                    <ButtonPrimary
                        type="submit"
                        :disabled="form.processing"
                        :loading="form.processing"
                    >
                        {{ $t('feedback.show.actions.update') }}
                    </ButtonPrimary>
                </div>
            </form>
        </Card>
    </ContainerPage>
</template>
