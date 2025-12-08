<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import FormRadio from '@/Components/FormRadio.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { useForm } from '@inertiajs/vue3';

interface Props {
    uiComplexity: 'simple' | 'advanced';
}

const props = defineProps<Props>();

const form = useForm({
    ui_complexity: props.uiComplexity,
});

const submit = () => {
    form.patch(route('profile.ui-complexity.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Interface Complexity
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Choose how much detail you'd like to see when creating prompts.
                Simple mode focuses on essential inputs and outputs, whilst
                advanced mode shows additional technical details and insights.
            </p>
        </header>

        <form class="mt-6 space-y-6" @submit.prevent="submit">
            <div>
                <InputLabel
                    for="ui_complexity"
                    value="Interface Mode"
                    :required="true"
                />
                <div class="mt-3 space-y-3">
                    <FormRadio
                        id="ui-simple"
                        v-model="form.ui_complexity"
                        name="ui_complexity"
                        value="simple"
                        label="Simple"
                        help-text="Shows only essential features. Hides task classification, cognitive requirements, and advanced technical details."
                        :required="true"
                    />

                    <FormRadio
                        id="ui-advanced"
                        v-model="form.ui_complexity"
                        name="ui_complexity"
                        value="advanced"
                        label="Advanced"
                        help-text="Shows all features including task classification, cognitive requirements, and personality insights."
                        :required="true"
                    />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <ButtonPrimary
                    type="submit"
                    :disabled="form.processing"
                    :loading="form.processing"
                >
                    Save
                </ButtonPrimary>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
