<script setup lang="ts">
import ButtonSmall from '@/Components/Base/Button/ButtonSmall.vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    modelValue: string;
    title: string;
    placeholder?: string;
    readonly?: boolean;
    collapsed?: boolean;
    showSave?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: '',
    readonly: false,
    collapsed: false,
    showSave: true,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    expand: [];
    save: [];
}>();

const { t } = useI18n();
const placeholderText = computed(
    () => props.placeholder || t('workflow.codeEditor.placeholder'),
);

const characterCount = computed(() => {
    return props.modelValue
        ? t('workflow.codeEditor.characters', {
              count: props.modelValue.length,
          })
        : t('workflow.codeEditor.notAvailable');
});
</script>

<template>
    <div class="flex flex-col overflow-hidden rounded-lg bg-white shadow-md">
        <div class="flex items-center justify-between bg-indigo-300 px-6 py-4">
            <span class="font-semibold text-indigo-800">{{ title }}</span>
            <div class="flex gap-2">
                <ButtonSmall
                    :title="$t('workflow.codeEditor.expandTitle')"
                    @click="emit('expand')"
                >
                    {{ $t('workflow.codeEditor.expand') }}
                </ButtonSmall>
                <ButtonSmall
                    v-if="!readonly && showSave"
                    :title="$t('workflow.codeEditor.saveTitle')"
                    @click="emit('save')"
                >
                    {{ $t('workflow.codeEditor.save') }}
                </ButtonSmall>
            </div>
        </div>
        <div v-if="collapsed" class="flex-1 overflow-auto bg-indigo-100 p-6">
            <p class="text-xs text-indigo-600 italic">
                {{ $t('workflow.codeEditor.collapsedHint') }}
            </p>
        </div>
        <textarea
            v-else
            :value="modelValue"
            :placeholder="placeholderText"
            :readonly="readonly"
            class="flex-1 resize-none border-0 bg-indigo-100 p-6 font-mono text-xs text-black focus:outline-none"
            @input="
                emit(
                    'update:modelValue',
                    ($event.target as HTMLTextAreaElement).value,
                )
            "
        ></textarea>
        <div
            class="border-t border-indigo-200 bg-indigo-50 px-6 py-2 text-xs text-indigo-700"
        >
            {{ characterCount }}
        </div>
    </div>
</template>
