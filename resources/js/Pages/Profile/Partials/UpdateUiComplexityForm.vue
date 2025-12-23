<script setup lang="ts">
import CollapsibleSection from '@/Components/Base/CollapsibleSection.vue';
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

// Auto-save when the selected option changes
watch(
    () => form.uiComplexity,
    () => {
        form.patch(route('profile.ui-complexity.update'), {
            preserveScroll: true,
        });
    },
);
</script>

<template>
    <section>
        <CollapsibleSection
            title="Interface Complexity"
            subtitle="Choose how much detail you'd like to see when creating prompts."
            data-testid="ui-complexity"
            icon="sliders-horizontal"
        >
            <div class="space-y-6">
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
            </div>
        </CollapsibleSection>
    </section>
</template>
