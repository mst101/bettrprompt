<script setup lang="ts">
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import Card from '@/Components/Base/Card.vue';
import VisitorLimitModal from '@/Components/Common/VisitorLimitModal.vue';
import { useAlert } from '@/Composables/ui/useAlert';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import { analyticsService } from '@/services/analytics';
import type { PromptRunResource } from '@/Types';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, withDefaults } from 'vue';
import { useI18n } from 'vue-i18n';

interface Framework {
    name: string;
    code: string;
    when_to_use_instead: string;
}

interface Props {
    frameworks: Framework[];
    promptRunId: number;
    promptRun: PromptRunResource;
    currentFramework?: { code: string; slug?: string } | null;
    visitorHasCompletedPrompts?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    visitorHasCompletedPrompts: false,
});

const switchingFramework = ref<string | null>(null);
const showVisitorLimitModal = ref(false);
const { confirm } = useAlert();
const { t } = useI18n({ useScope: 'global' });
const { countryRoute } = useCountryRoute();

const page = usePage();
const user = computed(() => page.props.auth?.user);

const handleSwitchFramework = async (frameworkCode: string) => {
    // Check if unregistered visitor has completed prompts
    if (!user.value && props.visitorHasCompletedPrompts) {
        showVisitorLimitModal.value = true;
        return;
    }

    const confirmed = await confirm(
        t('promptBuilder.components.alternativeFrameworks.confirm.message'),
        t('promptBuilder.components.alternativeFrameworks.confirm.title'),
    );

    if (!confirmed) {
        return;
    }

    switchingFramework.value = frameworkCode;

    // Track framework switch event
    analyticsService.track({
        name: 'framework_switched',
        properties: {
            prompt_run_id: props.promptRun.id,
            from_framework:
                props.currentFramework?.slug ||
                props.currentFramework?.code ||
                'unknown',
            to_framework: frameworkCode,
            personality_type: props.promptRun.personalityType,
            task_category: props.promptRun.taskCategory,
        },
    });

    router.post(
        countryRoute('prompt-builder.create-child-with-framework', {
            promptRun: props.promptRunId,
        }),
        {
            framework_code: frameworkCode,
        },
        {
            onSuccess: (page) => {
                // The response should contain the new promptRun ID in the props
                const newPromptRunId = page.props.promptRun?.id;

                // Force a fresh navigation to the new promptRun's analyse page
                // This ensures the component mounts with completely fresh props
                if (newPromptRunId) {
                    router.visit(
                        countryRoute('prompt-builder.show', {
                            promptRun: newPromptRunId,
                        }),
                        {
                            method: 'get',
                            preserveScroll: true,
                        },
                    );
                }
            },
            onError: (errors) => {
                console.error(
                    '[AlternativeFrameworks] Framework switch failed:',
                    errors,
                );
            },
            onFinish: () => {
                switchingFramework.value = null;
            },
        },
    );
};
</script>

<template>
    <!-- Visitor limit modal -->
    <VisitorLimitModal
        :show="showVisitorLimitModal"
        @close="showVisitorLimitModal = false"
    />

    <Card v-if="frameworks.length > 0" class="space-y-3">
        <h2 class="mb-4 text-lg font-semibold text-indigo-900">
            {{ $t('promptBuilder.components.alternativeFrameworks.title') }}
        </h2>
        <div
            v-for="framework in frameworks"
            :key="framework.code"
            class="rounded-lg bg-indigo-50 p-4 dark:bg-indigo-100"
        >
            <div class="mb-2 flex items-start justify-between">
                <h3 class="font-medium text-indigo-900">
                    {{ framework.name }}
                </h3>
            </div>
            <div
                class="mt-2 flex flex-col sm:flex-row sm:justify-between sm:space-x-4"
            >
                <p class="text-sm text-indigo-700">
                    <span class="font-bold">
                        {{
                            $t(
                                'promptBuilder.components.alternativeFrameworks.whenToUse',
                            )
                        }}
                    </span>
                    {{ framework.when_to_use_instead }}
                </p>
                <div class="mt-4 sm:whitespace-nowrap">
                    <ButtonSecondary
                        type="button"
                        class="w-full bg-indigo-100 hover:bg-indigo-200 focus:ring-offset-indigo-200 dark:bg-indigo-200 dark:hover:bg-indigo-300"
                        icon="arrow-right"
                        icon-position="right"
                        :disabled="switchingFramework !== null"
                        :loading="switchingFramework === framework.code"
                        @click="handleSwitchFramework(framework.code)"
                    >
                        {{
                            $t(
                                'promptBuilder.components.alternativeFrameworks.useButton',
                            )
                        }}
                    </ButtonSecondary>
                </div>
            </div>
        </div>
    </Card>
    <Card v-else>
        <div class="text-center text-indigo-500">
            {{ $t('promptBuilder.components.alternativeFrameworks.empty') }}
        </div>
    </Card>
</template>
