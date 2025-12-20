<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import LinkButton from '@/Components/Base/LinkButton.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const buttonText = computed(() => {
    return page.props.auth?.user ? 'Try It Now' : 'Get Started for Free';
});
</script>

<template>
    <div class="my-12 flex flex-col items-center">
        <LinkButton
            variant="primary"
            size="md"
            :href="route('prompt-builder.index')"
            class="sm:px-6 sm:py-3 sm:text-base"
        >
            <div class="flex items-center gap-3">
                <span>{{ buttonText }}</span>
                <DynamicIcon name="arrow-right" class="h-5 w-5 sm:h-6 sm:w-6" />
            </div>
        </LinkButton>
    </div>
</template>
