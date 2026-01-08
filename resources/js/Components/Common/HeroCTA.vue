<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import LinkButton from '@/Components/Base/LinkButton.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const page = usePage();
const { t } = useI18n({ useScope: 'global' });

const buttonText = computed(() => {
    return page.props.auth?.user
        ? t('components.common.heroCta.tryNow')
        : t('components.common.heroCta.getStartedFree');
});
</script>

<template>
    <div class="my-10 flex flex-col items-center sm:my-16">
        <LinkButton
            variant="primary"
            size="lg"
            :href="localeRoute('prompt-builder.index')"
            class="sm:px-6 sm:py-3 sm:text-base"
        >
            <div class="flex items-center gap-3">
                <span>{{ buttonText }}</span>
                <DynamicIcon name="arrow-right" class="h-5 w-5 sm:h-6 sm:w-6" />
            </div>
        </LinkButton>
    </div>
</template>
