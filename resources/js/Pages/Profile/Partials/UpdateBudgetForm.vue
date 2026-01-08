<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import CollapsibleSection from '@/Components/Base/CollapsibleSection.vue';
import FormRadio from '@/Components/Base/Form/FormRadio.vue';
import InputLabel from '@/Components/Base/InputLabel.vue';
import ButtonTrash from '@/Components/Common/ButtonTrash.vue';
import { useAlert } from '@/Composables/ui/useAlert';
import { useNotification } from '@/Composables/ui/useNotification';
import { useLocaleRoute } from '@/Composables/useLocaleRoute';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    budgetData: {
        budgetConsciousness: string | null;
    };
}

const props = defineProps<Props>();
const { success, error } = useNotification();
const { t } = useI18n({ useScope: 'global' });
const { localeRoute } = useLocaleRoute();

const budgetOptions = computed(() => [
    {
        value: 'free_only',
        label: t('profile.budget.options.freeOnly.label'),
        description: t('profile.budget.options.freeOnly.description'),
    },
    {
        value: 'free_first',
        label: t('profile.budget.options.freeFirst.label'),
        description: t('profile.budget.options.freeFirst.description'),
    },
    {
        value: 'mixed',
        label: t('profile.budget.options.mixed.label'),
        description: t('profile.budget.options.mixed.description'),
    },
    {
        value: 'premium_ok',
        label: t('profile.budget.options.premiumOk.label'),
        description: t('profile.budget.options.premiumOk.description'),
    },
    {
        value: 'enterprise',
        label: t('profile.budget.options.enterprise.label'),
        description: t('profile.budget.options.enterprise.description'),
    },
]);

const form = useForm({
    budgetConsciousness: props.budgetData.budgetConsciousness || '',
});

watch(
    () => form.recentlySuccessful,
    (value) => {
        if (value) {
            success(t('profile.budget.notifications.updated'));
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
    form.patch(localeRoute('profile.budget.update'), {
        preserveScroll: true,
    });
};

const hasBudgetData = computed(() => {
    return !!props.budgetData.budgetConsciousness;
});

const { confirm } = useAlert();

const clearBudget = async () => {
    const confirmed = await confirm(
        t('profile.budget.clearConfirm.message'),
        t('profile.budget.clearConfirm.title'),
        {
            confirmButtonStyle: 'danger',
            confirmText: t('common.buttons.clear'),
        },
    );

    if (confirmed) {
        const clearForm = useForm({});
        clearForm.delete(localeRoute('profile.budget.clear'), {
            preserveScroll: true,
            onSuccess: () => {
                success(t('profile.budget.notifications.cleared'));
                // Clear the form field
                form.budgetConsciousness = '';
            },
            onError: () => {
                error(t('profile.budget.notifications.clearFailed'));
            },
        });
    }
};
</script>

<template>
    <CollapsibleSection
        :title="$t('profile.budget.title')"
        :subtitle="$t('profile.budget.subtitle')"
        data-testid="budget"
        icon="chart-bar"
    >
        <form class="space-y-6" @submit.prevent="submit">
            <!-- Budget Consciousness -->
            <div>
                <InputLabel
                    for="budget-free-only"
                    :value="$t('profile.budget.fieldLabel')"
                    :required="false"
                />

                <div class="mt-4 space-y-4">
                    <div
                        v-for="option in budgetOptions"
                        :key="option.value"
                        class="cursor-pointer rounded-lg border border-indigo-200 bg-indigo-50 p-4 hover:border-indigo-100 hover:bg-indigo-100"
                        :class="{
                            'border-indigo-500 bg-indigo-50 dark:bg-indigo-100':
                                form.budgetConsciousness === option.value,
                        }"
                        @click="form.budgetConsciousness = option.value"
                    >
                        <FormRadio
                            :id="`budget-${option.value}`"
                            v-model="form.budgetConsciousness"
                            :value="option.value"
                            :name="`budget-${option.value}`"
                            :label="option.label"
                        />
                        <p class="mt-2 ml-6 text-sm text-indigo-600">
                            {{ option.description }}
                        </p>
                    </div>
                </div>

                <p
                    v-if="form.errors.budgetConsciousness"
                    class="mt-2 text-sm text-red-600"
                >
                    {{ form.errors.budgetConsciousness }}
                </p>
            </div>

            <div class="flex flex-col items-center gap-4 sm:flex-row">
                <ButtonPrimary
                    type="submit"
                    :disabled="form.processing"
                    :loading="form.processing"
                    icon="download"
                >
                    {{ $t('profile.budget.actions.save') }}
                </ButtonPrimary>

                <ButtonTrash
                    v-if="hasBudgetData"
                    id="clear-budget-form"
                    :label="$t('common.buttons.clear')"
                    @clear="clearBudget"
                />
            </div>
        </form>
    </CollapsibleSection>
</template>
