<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import CollapsibleSection from '@/Components/Base/CollapsibleSection.vue';
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import ButtonTrash from '@/Components/Common/ButtonTrash.vue';
import { useAlert } from '@/Composables/ui/useAlert';
import { useNotification } from '@/Composables/ui/useNotification';
import { useLocaleRoute } from '@/Composables/useLocaleRoute';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    teamData: {
        teamSize: string | null;
        teamRole: string | null;
        workMode: string | null;
    };
}

const props = defineProps<Props>();
const { success, error } = useNotification();
const { t } = useI18n();
const { localeRoute } = useLocaleRoute();

const teamSizeOptions = computed(() => [
    { value: 'solo', label: t('profile.team.options.size.solo') },
    { value: 'small', label: t('profile.team.options.size.small') },
    { value: 'medium', label: t('profile.team.options.size.medium') },
    { value: 'large', label: t('profile.team.options.size.large') },
]);

const teamRoleOptions = computed(() => [
    { value: 'individual', label: t('profile.team.options.role.individual') },
    { value: 'lead', label: t('profile.team.options.role.lead') },
    { value: 'manager', label: t('profile.team.options.role.manager') },
    { value: 'director', label: t('profile.team.options.role.director') },
    { value: 'executive', label: t('profile.team.options.role.executive') },
]);

const workModeOptions = computed(() => [
    { value: 'office', label: t('profile.team.options.mode.office') },
    { value: 'hybrid', label: t('profile.team.options.mode.hybrid') },
    { value: 'remote', label: t('profile.team.options.mode.remote') },
    { value: 'freelance', label: t('profile.team.options.mode.freelance') },
]);

const form = useForm({
    teamSize: props.teamData.teamSize || '',
    teamRole: props.teamData.teamRole || '',
    workMode: props.teamData.workMode || '',
});

watch(
    () => form.recentlySuccessful,
    (value) => {
        if (value) {
            success(t('profile.team.notifications.updated'));
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
    form.patch(localeRoute('profile.team.update'), {
        preserveScroll: true,
    });
};

const hasTeamData = computed(() => {
    return !!(
        props.teamData.teamSize ||
        props.teamData.teamRole ||
        props.teamData.workMode
    );
});

const { confirm } = useAlert();

const clearTeam = async () => {
    const confirmed = await confirm(
        t('profile.team.clearConfirm.message'),
        t('profile.team.clearConfirm.title'),
        {
            confirmButtonStyle: 'danger',
            confirmText: t('common.buttons.clear'),
        },
    );

    if (confirmed) {
        const clearForm = useForm({});
        clearForm.delete(localeRoute('profile.team.clear'), {
            preserveScroll: true,
            onSuccess: () => {
                success(t('profile.team.notifications.cleared'));
                // Clear the form fields
                form.teamSize = '';
                form.teamRole = '';
                form.workMode = '';
            },
            onError: () => {
                error(t('profile.team.notifications.clearFailed'));
            },
        });
    }
};
</script>

<template>
    <CollapsibleSection
        :title="$t('profile.team.title')"
        :subtitle="$t('profile.team.subtitle')"
        data-testid="team"
        icon="users"
    >
        <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-4 sm:grid sm:grid-cols-2 sm:gap-6 sm:space-y-0">
                <!-- Team Size -->
                <FormSelect
                    id="team-size"
                    v-model="form.teamSize"
                    :label="$t('profile.team.fields.teamSize')"
                    :options="teamSizeOptions"
                    :error="form.errors.teamSize"
                    :placeholder="$t('profile.team.placeholders.teamSize')"
                    show-placeholder
                />

                <!-- Team Role -->
                <FormSelect
                    id="team-role"
                    v-model="form.teamRole"
                    :label="$t('profile.team.fields.role')"
                    :options="teamRoleOptions"
                    :error="form.errors.teamRole"
                    :placeholder="$t('profile.team.placeholders.role')"
                    show-placeholder
                />

                <!-- Work Mode -->
                <FormSelect
                    id="work-mode"
                    v-model="form.workMode"
                    :label="$t('profile.team.fields.workMode')"
                    :options="workModeOptions"
                    :error="form.errors.workMode"
                    :placeholder="$t('profile.team.placeholders.workMode')"
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
                    {{ $t('profile.team.actions.save') }}
                </ButtonPrimary>

                <ButtonTrash
                    v-if="hasTeamData"
                    id="clear-team-form"
                    :label="$t('common.buttons.clear')"
                    @clear="clearTeam"
                />
            </div>
        </form>
    </CollapsibleSection>
</template>
