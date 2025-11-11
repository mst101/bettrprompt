<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import { useTextAppend } from '@/Composables/useTextAppend';
import { useForm } from '@inertiajs/vue3';

interface Props {
    promptRunId: number;
    initialTaskDescription: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'cancel'): void;
}>();

const { appendText } = useTextAppend();

const form = useForm({
    task_description: props.initialTaskDescription,
});

const handleTranscription = (transcript: string) => {
    form.task_description = appendText(form.task_description, transcript);
};

const submit = () => {
    form.post(route('prompt-optimizer.create-child', props.promptRunId), {
        preserveScroll: true,
        onSuccess: () => {
            // Redirect happens automatically via controller
        },
    });
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4">
        <FormTextarea
            id="task_description"
            v-model="form.task_description"
            label="Edit Task Description"
            :rows="6"
            :error="form.errors.task_description"
            required
            autofocus
            placeholder="Describe the task you want to create a prompt for..."
        />

        <div class="flex items-center justify-between">
            <ButtonVoiceInput @transcription="handleTranscription" />

            <div class="flex gap-3">
                <ButtonSecondary
                    type="button"
                    @click="emit('cancel')"
                    :disabled="form.processing"
                >
                    Cancel
                </ButtonSecondary>
                <ButtonPrimary
                    type="submit"
                    :disabled="
                        !form.task_description ||
                        form.task_description.trim() === '' ||
                        form.processing
                    "
                    :loading="form.processing"
                >
                    Optimise Prompt
                </ButtonPrimary>
            </div>
        </div>
    </form>
</template>
