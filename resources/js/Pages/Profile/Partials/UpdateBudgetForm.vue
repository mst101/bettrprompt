<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import CollapsibleSection from '@/Components/Base/CollapsibleSection.vue';
import FormRadio from '@/Components/Base/Form/FormRadio.vue';
import InputLabel from '@/Components/Base/InputLabel.vue';
import ButtonTrash from '@/Components/Common/ButtonTrash.vue';
import { useAlert } from '@/Composables/ui/useAlert';
import { useNotification } from '@/Composables/ui/useNotification';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Props {
    budgetData: {
        budgetConsciousness: string | null;
    };
}

const props = defineProps<Props>();
const { success, error } = useNotification();

const budgetOptions = [
    {
        value: 'free_only',
        label: 'Free Only',
        description: 'I only use free tools and services',
    },
    {
        value: 'free_first',
        label: 'Prefer Free',
        description: 'I prefer free options but will pay if necessary',
    },
    {
        value: 'mixed',
        label: 'Mixed',
        description: 'I use a mix of free and premium tools',
    },
    {
        value: 'premium_ok',
        label: 'Premium OK',
        description: "I'm comfortable using premium tools",
    },
    {
        value: 'enterprise',
        label: 'Enterprise',
        description: 'I have access to enterprise solutions',
    },
];

const form = useForm({
    budgetConsciousness: props.budgetData.budgetConsciousness || '',
});

watch(
    () => form.recentlySuccessful,
    (value) => {
        if (value) {
            success('Budget preferences updated successfully');
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
    form.patch(route('profile.budget.update'), {
        preserveScroll: true,
    });
};

const hasBudgetData = computed(() => {
    return !!props.budgetData.budgetConsciousness;
});

const { confirm } = useAlert();

const clearBudget = async () => {
    const confirmed = await confirm(
        'Are you sure you want to clear your budget preferences?',
        'Clear Budget Preferences',
        { confirmButtonStyle: 'danger', confirmText: 'Clear' },
    );

    if (confirmed) {
        const clearForm = useForm({});
        clearForm.delete(route('profile.budget.clear'), {
            preserveScroll: true,
            onSuccess: () => {
                success('Budget preferences cleared successfully');
                // Clear the form field
                form.budgetConsciousness = '';
            },
            onError: () => {
                error('Failed to clear budget preferences');
            },
        });
    }
};
</script>

<template>
    <CollapsibleSection
        title="Budget & Tool Preferences"
        subtitle="Tell us about your budget for tools and services so we can recommend appropriate solutions."
    >
        <form class="space-y-6" @submit.prevent="submit">
            <!-- Budget Consciousness -->
            <div>
                <InputLabel
                    for="budget-free-only"
                    value="Budget Preference"
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
                    Save Budget Preferences
                </ButtonPrimary>

                <ButtonTrash
                    v-if="hasBudgetData"
                    id="clear-budget-form"
                    label="Clear"
                    @clear="clearBudget"
                />
            </div>
        </form>
    </CollapsibleSection>
</template>
