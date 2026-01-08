<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormCheckboxGroup from '@/Components/Base/Form/FormCheckboxGroup.vue';
import FormTextarea from '@/Components/Base/Form/FormTextarea.vue';
import LikertScale from '@/Components/Base/LikertScale.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useLocaleRoute } from '@/Composables/useLocaleRoute';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

defineOptions({
    layout: AppLayout,
});

const form = useForm({
    experienceLevel: null,
    usefulness: null,
    usageIntent: null,
    suggestions: '',
    desiredFeatures: [] as string[],
    desiredFeaturesOther: '',
});

const { t } = useI18n();
const { localeRoute } = useLocaleRoute();

const hasErrors = computed(() => Object.keys(form.errors).length > 0);

const featureOptions = computed(() => [
    {
        value: 'document-upload',
        label: t('feedback.features.documentUpload.label'),
        description: t('feedback.features.documentUpload.description'),
    },
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
        value: 'other',
        label: t('feedback.features.other.label'),
        description: t('feedback.features.other.description'),
    },
]);

const submit = () => {
    form.post(localeRoute('feedback.store'), {
        onSuccess: () => {
            // Redirect will be handled by controller
        },
    });
};
</script>

<template>
    <Head :title="$t('feedback.create.title')" />

    <HeaderPage :title="$t('feedback.create.heading')" />

    <ContainerPage>
        <Card>
            <div class="mb-6">
                <h2 class="font-semibold text-indigo-900">
                    {{ $t('feedback.create.intro.title') }}
                </h2>
                <p class="mt-1 text-indigo-600">
                    {{ $t('feedback.create.intro.description') }}
                </p>
            </div>

            <!-- Error Alert -->
            <div
                v-if="hasErrors"
                class="mb-6 rounded-lg border border-red-300 bg-red-50 p-4"
                role="alert"
            >
                <div class="flex items-start gap-3">
                    <DynamicIcon
                        name="exclamation-circle"
                        class="mt-0.5 h-5 w-5 shrink-0 text-red-600"
                    />
                    <div class="flex-1">
                        <h3 class="font-semibold text-red-900">
                            {{ $t('feedback.create.errors.title') }}
                        </h3>
                        <ul
                            class="mt-2 list-inside list-disc space-y-1 text-red-700"
                        >
                            <li
                                v-if="form.errors.experienceLevel"
                                key="experience-level"
                            >
                                <strong>{{
                                    $t(
                                        'feedback.create.errors.labels.experienceLevel',
                                    )
                                }}</strong>
                                {{ form.errors.experienceLevel }}
                            </li>
                            <li v-if="form.errors.usefulness" key="usefulness">
                                <strong>{{
                                    $t(
                                        'feedback.create.errors.labels.usefulness',
                                    )
                                }}</strong>
                                {{ form.errors.usefulness }}
                            </li>
                            <li
                                v-if="form.errors.usageIntent"
                                key="usage-intent"
                            >
                                <strong>{{
                                    $t(
                                        'feedback.create.errors.labels.usageIntent',
                                    )
                                }}</strong>
                                {{ form.errors.usageIntent }}
                            </li>
                            <li
                                v-if="form.errors.suggestions"
                                key="suggestions"
                            >
                                <strong>{{
                                    $t(
                                        'feedback.create.errors.labels.suggestions',
                                    )
                                }}</strong>
                                {{ form.errors.suggestions }}
                            </li>
                            <li
                                v-if="form.errors.desiredFeatures"
                                key="desired-features"
                            >
                                <strong>{{
                                    $t(
                                        'feedback.create.errors.labels.desiredFeatures',
                                    )
                                }}</strong>
                                {{ form.errors.desiredFeatures }}
                            </li>
                            <li
                                v-if="form.errors.desiredFeaturesOther"
                                key="desired-features-other"
                            >
                                <strong>{{
                                    $t(
                                        'feedback.create.errors.labels.desiredFeaturesOther',
                                    )
                                }}</strong>
                                {{ form.errors.desiredFeaturesOther }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <form class="space-y-8" @submit.prevent="submit">
                <!-- Question 1: Experience Level -->
                <div
                    class="mt-8 rounded-lg p-4 transition"
                    :class="
                        form.errors.experienceLevel
                            ? 'border-2 border-red-300 bg-red-50'
                            : ''
                    "
                >
                    <label
                        class="mb-4 block text-sm font-medium text-indigo-900"
                    >
                        {{ $t('feedback.questions.experience.label') }}
                    </label>
                    <LikertScale
                        v-model="form.experienceLevel"
                        :left-label="$t('feedback.questions.experience.left')"
                        :right-label="$t('feedback.questions.experience.right')"
                        :disabled="form.processing"
                    />
                    <p
                        v-if="form.errors.experienceLevel"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.experienceLevel }}
                    </p>
                </div>

                <!-- Question 2: Usefulness -->
                <div
                    class="mt-16 rounded-lg p-4 transition"
                    :class="
                        form.errors.usefulness
                            ? 'border-2 border-red-300 bg-red-50'
                            : ''
                    "
                >
                    <label
                        class="mb-4 block text-sm font-medium text-indigo-900"
                    >
                        {{ $t('feedback.questions.usefulness.label') }}
                    </label>
                    <LikertScale
                        v-model="form.usefulness"
                        :left-label="$t('feedback.questions.usefulness.left')"
                        :right-label="$t('feedback.questions.usefulness.right')"
                        :disabled="form.processing"
                    />
                    <p
                        v-if="form.errors.usefulness"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.usefulness }}
                    </p>
                </div>

                <!-- Question 3: Usage Intent -->
                <div
                    class="mt-16 rounded-lg p-4 transition"
                    :class="
                        form.errors.usageIntent
                            ? 'border-2 border-red-300 bg-red-50'
                            : ''
                    "
                >
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
                        :disabled="form.processing"
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
                        :disabled="form.processing"
                        :placeholder="
                            $t('feedback.questions.suggestions.placeholder')
                        "
                        :rows="5"
                    />
                </div>

                <!-- Question 5: Desired Features -->
                <div
                    class="mt-16 rounded-lg p-4 transition"
                    :class="
                        form.errors.desiredFeatures ||
                        form.errors.desiredFeaturesOther
                            ? 'border-2 border-red-300 bg-red-50'
                            : ''
                    "
                >
                    <label
                        class="mb-4 block text-sm font-medium text-indigo-900"
                    >
                        {{ $t('feedback.questions.features.label') }}
                        <span class="font-normal text-indigo-600">{{
                            $t('feedback.questions.features.hint')
                        }}</span>
                    </label>
                    <p class="mb-3 text-xs text-indigo-600">
                        {{ $t('feedback.questions.features.note') }}
                    </p>
                    <FormCheckboxGroup
                        v-model="form.desiredFeatures"
                        v-model:other-value="form.desiredFeaturesOther"
                        :options="featureOptions"
                        :disabled="form.processing"
                        :error="form.errors.desiredFeatures"
                    />
                    <p
                        v-if="form.errors.desiredFeaturesOther"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.desiredFeaturesOther }}
                    </p>
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center justify-end gap-3">
                    <ButtonSecondary
                        type="button"
                        :disabled="form.processing"
                        @click="
                            $inertia.visit(
                                localeRoute('prompt-builder.history'),
                            )
                        "
                    >
                        {{ $t('common.buttons.cancel') }}
                    </ButtonSecondary>
                    <ButtonPrimary
                        type="submit"
                        :disabled="form.processing"
                        :loading="form.processing"
                    >
                        {{ $t('feedback.create.actions.submit') }}
                    </ButtonPrimary>
                </div>
            </form>
        </Card>
    </ContainerPage>
</template>
