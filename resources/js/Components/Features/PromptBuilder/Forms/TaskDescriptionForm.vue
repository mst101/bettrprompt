<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonVoiceInput from '@/Components/Base/Button/ButtonVoiceInput.vue';
import FormTextareaWithActions from '@/Components/Base/Form/FormTextareaWithActions.vue';
import LinkText from '@/Components/Base/LinkText.vue';
import ButtonTrash from '@/Components/Common/ButtonTrash.vue';
import { usePersonalityPromptPreference } from '@/Composables/features/usePersonalityPromptPreference';
import { useLocaleRoute } from '@/Composables/useLocaleRoute';
import type { InertiaForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
const { localeRoute } = useLocaleRoute();
const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);
const { isDismissed } = usePersonalityPromptPreference();

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
            {{ $t('promptBuilder.components.taskDescriptionForm.intro') }}
            <span v-if="hasPersonalityType">
                {{
                    $t(
                        'promptBuilder.components.taskDescriptionForm.withPersonality',
                    )
                }}
            </span>
            <span v-else>
                {{
                    $t(
                        'promptBuilder.components.taskDescriptionForm.withoutPersonality',
                    )
                }}
                <span v-if="isAuthenticated && isDismissed" class="block">
                    <i18n-t
                        keypath="promptBuilder.components.taskDescriptionForm.profileHint"
                        scope="global"
                        tag="span"
                    >
                        <template #link>
                            <LinkText :href="localeRoute('profile.edit')">
                                {{
                                    $t(
                                        'promptBuilder.components.taskDescriptionForm.profileHintLink',
                                    )
                                }}
                            </LinkText>
                        </template>
                    </i18n-t>
                </span>
            </span>
        </p>

        <form class="max-w-4xl space-y-6" @submit.prevent="emit('submit')">
            <!-- Task Description -->
            <FormTextareaWithActions
                id="task-description"
                ref="taskDescriptionTextarea"
                :model-value="form.taskDescription"
                :label="
                    $t('promptBuilder.components.taskDescriptionForm.label')
                "
                :error="form.errors.taskDescription"
                :help-text="
                    $t('promptBuilder.components.taskDescriptionForm.helpText')
                "
                required
                :placeholder="
                    $t(
                        'promptBuilder.components.taskDescriptionForm.placeholder',
                    )
                "
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
                    class="w-full sm:w-fit"
                    type="submit"
                    icon="arrow-right"
                    icon-position="right"
                    :disabled="
                        form.processing || form.taskDescription.length < 10
                    "
                >
                    <span v-if="form.processing">
                        {{
                            $t(
                                'promptBuilder.components.taskDescriptionForm.processing',
                            )
                        }}
                    </span>
                    <span v-else>
                        {{
                            $t(
                                'promptBuilder.components.taskDescriptionForm.submit',
                            )
                        }}
                    </span>
                </ButtonPrimary>
            </div>
        </form>
    </div>
</template>
