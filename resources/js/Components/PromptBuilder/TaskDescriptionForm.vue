<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import FormTextareaWithActions from '@/Components/FormTextareaWithActions.vue';
import ButtonTrash from '@/Components/PromptBuilder/ButtonTrash.vue';
import type { InertiaForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    hasPersonalityType: boolean;
    form: InertiaForm<{ taskDescription: string }>;
}

defineProps<Props>();

const emit = defineEmits<{
    (e: 'submit'): void;
    (e: 'transcription', text: string): void;
    (e: 'clear'): void;
    (e: 'update:taskDescription', value: string): void;
}>();

const taskDescriptionTextarea = ref<InstanceType<
    typeof FormTextareaWithActions
> | null>(null);

// Expose focus method to parent
const focus = () => {
    taskDescriptionTextarea.value?.focus();
};

defineExpose({ focus });
</script>

<template>
    <div>
        <p class="mb-6 max-w-4xl text-indigo-700">
            Create optimised AI prompts using expert frameworks.
            <span v-if="hasPersonalityType">
                Prompts will be customised to your personality type and task
                requirements.
            </span>
            <span v-else>
                Prompts will be optimised for your specific task requirements.
            </span>
        </p>

        <form class="max-w-4xl space-y-6" @submit.prevent="emit('submit')">
            <!-- Task Description -->
            <FormTextareaWithActions
                id="task-description"
                ref="taskDescriptionTextarea"
                :model-value="form.taskDescription"
                label="Task Description"
                :error="form.errors.taskDescription"
                help-text="Minimum 10 characters. Be specific about your goals and requirements."
                required
                autofocus
                :rows="6"
                placeholder="Describe what you're trying to accomplish..."
                @update:model-value="
                    (value) => emit('update:taskDescription', value)
                "
            >
                <template #actions>
                    <ButtonTrash
                        v-if="form.taskDescription"
                        class="mr-2"
                        @click="emit('clear')"
                    />
                    <ButtonVoiceInput
                        @transcription="(text) => emit('transcription', text)"
                    />
                </template>
            </FormTextareaWithActions>

            <!-- Submit Button -->
            <div class="flex items-center justify-end">
                <ButtonPrimary
                    type="submit"
                    :disabled="
                        form.processing || form.taskDescription.length < 10
                    "
                >
                    <span v-if="form.processing">Processing...</span>
                    <span v-else>Optimise Prompt</span>
                </ButtonPrimary>
            </div>
        </form>
    </div>
</template>
