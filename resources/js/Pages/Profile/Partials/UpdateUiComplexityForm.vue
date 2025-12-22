<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import FormRadio from '@/Components/Base/Form/FormRadio.vue';
import InputLabel from '@/Components/Base/InputLabel.vue';
import { useNotification } from '@/Composables/ui/useNotification';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

interface Props {
    uiComplexity: 'simple' | 'advanced';
}

const props = defineProps<Props>();
const { success, error } = useNotification();

const form = useForm({
    uiComplexity: props.uiComplexity,
});

watch(
    () => form.recentlySuccessful,
    (value) => {
        if (value) {
            success('Interface complexity updated successfully');
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
    form.patch(route('profile.ui-complexity.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-indigo-900">
                Interface Complexity
            </h2>

            <p class="mt-1 text-sm text-indigo-600">
                Choose how much detail you'd like to see when creating prompts.
                Simple mode focuses on essential inputs and outputs, whilst
                advanced mode shows additional technical details and insights.
            </p>
        </header>

        <form class="mt-6 space-y-6" @submit.prevent="submit">
            <div>
                <InputLabel for="uiComplexity" value="Interface Mode" />
                <div class="mt-3 space-y-3">
                    <FormRadio
                        id="ui-simple"
                        v-model="form.uiComplexity"
                        name="uiComplexity"
                        value="simple"
                        label="Simple"
                        help-text="Shows only essential features. Hides task classification, cognitive requirements, and advanced technical details."
                    />

                    <FormRadio
                        id="ui-advanced"
                        v-model="form.uiComplexity"
                        name="uiComplexity"
                        value="advanced"
                        label="Advanced"
                        help-text="Shows all features including task classification, cognitive requirements, and personality insights."
                    />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <ButtonPrimary
                    type="submit"
                    :disabled="form.processing"
                    :loading="form.processing"
                    icon="download"
                >
                    Save
                </ButtonPrimary>
            </div>
        </form>
    </section>
</template>
