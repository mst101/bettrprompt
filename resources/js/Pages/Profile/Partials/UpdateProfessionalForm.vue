<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import CollapsibleSection from '@/Components/Base/CollapsibleSection.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import ButtonTrash from '@/Components/Common/ButtonTrash.vue';
import { useAlert } from '@/Composables/ui/useAlert';
import { useNotification } from '@/Composables/ui/useNotification';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    professionalData: {
        jobTitle: string | null;
        industry: string | null;
        experienceLevel: string | null;
        companySize: string | null;
    };
}

const props = defineProps<Props>();
const { success, error } = useNotification();
const { t } = useI18n({ useScope: 'global' });
const { countryRoute } = useCountryRoute();

const experienceLevelOptions = computed(() => [
    { value: 'entry', label: t('profile.professional.options.entry') },
    { value: 'mid', label: t('profile.professional.options.mid') },
    { value: 'senior', label: t('profile.professional.options.senior') },
    { value: 'expert', label: t('profile.professional.options.expert') },
]);

const companySizeOptions = computed(() => [
    { value: 'solo', label: t('profile.professional.companySize.solo') },
    { value: 'small', label: t('profile.professional.companySize.small') },
    { value: 'medium', label: t('profile.professional.companySize.medium') },
    { value: 'large', label: t('profile.professional.companySize.large') },
    {
        value: 'enterprise',
        label: t('profile.professional.companySize.enterprise'),
    },
]);

const form = useForm({
    jobTitle: props.professionalData.jobTitle || '',
    industry: props.professionalData.industry || '',
    experienceLevel: props.professionalData.experienceLevel || '',
    companySize: props.professionalData.companySize || '',
});

watch(
    () => form.recentlySuccessful,
    (value) => {
        if (value) {
            success(t('profile.professional.notifications.updated'));
        }
    },
);

watch(
    () => Object.keys(form.errors).length > 0,
    (hasErrors) => {
        if (hasErrors) {
            const errorMessage = Object.values(form.errors)[0];
            if (typeof errorMessage === 'string') {
                error(errorMessage);
            }
        }
    },
);

const submit = () => {
    form.patch(countryRoute('profile.professional.update'), {
        preserveScroll: true,
    });
};

const hasProfessionalData = computed(() => {
    return !!(
        props.professionalData.jobTitle ||
        props.professionalData.industry ||
        props.professionalData.experienceLevel ||
        props.professionalData.companySize
    );
});

const { confirm } = useAlert();

const clearProfessional = async () => {
    const confirmed = await confirm(
        t('profile.professional.clearConfirm.message'),
        t('profile.professional.clearConfirm.title'),
        {
            confirmButtonStyle: 'danger',
            confirmText: t('common.buttons.clear'),
        },
    );

    if (confirmed) {
        const clearForm = useForm({});
        clearForm.delete(countryRoute('profile.professional.clear'), {
            preserveScroll: true,
            onSuccess: () => {
                success(t('profile.professional.notifications.cleared'));
                // Clear the form fields
                form.jobTitle = '';
                form.industry = '';
                form.experienceLevel = '';
                form.companySize = '';
            },
            onError: () => {
                error(t('profile.professional.notifications.clearFailed'));
            },
        });
    }
};
</script>

<template>
    <CollapsibleSection
        :title="$t('profile.professional.title')"
        :subtitle="$t('profile.professional.subtitle')"
        data-testid="professional"
        icon="building-office"
    >
        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-6 sm:grid-cols-2">
                <!-- Job Title -->
                <FormInput
                    id="job-title"
                    v-model="form.jobTitle"
                    :label="$t('profile.professional.fields.jobTitle')"
                    :placeholder="
                        $t('profile.professional.placeholders.jobTitle')
                    "
                    :error="form.errors.jobTitle"
                    :help-text="$t('profile.professional.help.jobTitle')"
                />

                <!-- Industry -->
                <FormInput
                    id="industry"
                    v-model="form.industry"
                    :label="$t('profile.professional.fields.industry')"
                    :placeholder="
                        $t('profile.professional.placeholders.industry')
                    "
                    :error="form.errors.industry"
                    :help-text="$t('profile.professional.help.industry')"
                />

                <!-- Experience Level -->
                <FormSelect
                    id="experience-level"
                    v-model="form.experienceLevel"
                    :label="$t('profile.professional.fields.experienceLevel')"
                    :options="experienceLevelOptions"
                    :error="form.errors.experienceLevel"
                    :placeholder="
                        $t('profile.professional.placeholders.experienceLevel')
                    "
                    show-placeholder
                />

                <!-- Company Size -->
                <FormSelect
                    id="company-size"
                    v-model="form.companySize"
                    :label="$t('profile.professional.fields.companySize')"
                    :options="companySizeOptions"
                    :error="form.errors.companySize"
                    :placeholder="
                        $t('profile.professional.placeholders.companySize')
                    "
                    show-placeholder
                />
            </div>

            <div class="flex flex-col items-center gap-4 sm:flex-row">
                <ButtonPrimary
                    type="submit"
                    :disabled="form.processing"
                    :loading="form.processing"
                    icon="download"
                >
                    {{ $t('profile.professional.actions.save') }}
                </ButtonPrimary>

                <ButtonTrash
                    v-if="hasProfessionalData"
                    id="clear-professional-form"
                    :label="$t('common.buttons.clear')"
                    @clear="clearProfessional"
                />
            </div>
        </form>
    </CollapsibleSection>
</template>
