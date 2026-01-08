<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

interface Props {
    message: string;
}

function goToPromptBuilder() {
    router.visit(localeRoute('prompt-builder.index'));
}
</script>

<template>
    <Head :title="$t('subscription.success.title')" />

    <ContainerPage>
        <div class="flex min-h-[50vh] items-center justify-center">
            <div class="max-w-md text-center">
                <div
                    class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-green-100"
                >
                    <DynamicIcon name="check" class="h-8 w-8 text-green-600" />
                </div>

                <h1 class="mb-4 text-3xl font-bold text-indigo-900">
                    {{ message }}
                </h1>

                <p class="mb-8 text-indigo-600">
                    {{ $t('subscription.success.description') }}
                </p>

                <ButtonPrimary
                    data-testid="go-to-prompt-builder"
                    @click="goToPromptBuilder"
                >
                    {{ $t('subscription.success.cta') }}
                </ButtonPrimary>
            </div>
        </div>
    </ContainerPage>
</template>
