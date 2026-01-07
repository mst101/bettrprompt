<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
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

function goToPricing() {
    router.visit(route('pricing'));
}

function goToPromptBuilder() {
    router.visit(route('prompt-builder.index'));
}
</script>

<template>
    <Head title="Checkout Cancelled" />

    <ContainerPage>
        <div class="flex min-h-[50vh] items-center justify-center">
            <div class="max-w-md text-center">
                <div
                    class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100"
                >
                    <DynamicIcon
                        name="x-mark"
                        class="h-8 w-8 text-indigo-600"
                    />
                </div>

                <h1 class="mb-4 text-3xl font-bold text-indigo-900">
                    Checkout Cancelled
                </h1>

                <p class="mb-8 text-indigo-600">
                    {{ message }} No worries - you can continue using the free
                    tier or try again whenever you're ready.
                </p>

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <ButtonSecondary @click="goToPromptBuilder">
                        Continue with Free
                    </ButtonSecondary>
                    <ButtonPrimary
                        data-testid="try-again-button"
                        @click="goToPricing"
                    >
                        View Pricing
                    </ButtonPrimary>
                </div>
            </div>
        </div>
    </ContainerPage>
</template>
