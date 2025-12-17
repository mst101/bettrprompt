<script setup lang="ts">
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import Card from '@/Components/Card.vue';
import { useAlert } from '@/Composables/useAlert';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Framework {
    name: string;
    code: string;
    when_to_use_instead: string;
}

interface Props {
    frameworks: Framework[];
    promptRunId: number;
}

const props = defineProps<Props>();

const switchingFramework = ref<string | null>(null);
const { confirm } = useAlert();

const handleSwitchFramework = async (frameworkCode: string) => {
    const confirmed = await confirm(
        'This will create a new prompt run using this framework. The analysis will be re-run with framework-specific questions. Continue?',
        'Switch Framework',
    );

    if (!confirmed) {
        return;
    }

    switchingFramework.value = frameworkCode;

    router.post(
        route('prompt-builder.create-child-with-framework', props.promptRunId),
        {
            framework_code: frameworkCode,
        },
        {
            onSuccess: (page) => {
                // The response should contain the new promptRun ID in the props
                const newPromptRunId = page.props.promptRun?.id;

                // Force a fresh navigation to the new promptRun's show page
                // This ensures the component mounts with completely fresh props
                if (newPromptRunId) {
                    router.visit(route('prompt-builder.show', newPromptRunId), {
                        method: 'get',
                        preserveScroll: true,
                    });
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
    <Card v-if="frameworks.length > 0" class="space-y-3">
        <h2 class="mb-4 text-lg font-semibold text-indigo-900">
            Alternative Frameworks
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
                    <span class="font-bold">When to use:</span>
                    {{ framework.when_to_use_instead }}
                </p>
                <div class="mt-4 sm:whitespace-nowrap">
                    <ButtonSecondary
                        type="button"
                        class="w-full focus:ring-offset-indigo-200 dark:bg-indigo-200 dark:hover:bg-indigo-300"
                        :disabled="switchingFramework !== null"
                        :loading="switchingFramework === framework.code"
                        @click="handleSwitchFramework(framework.code)"
                    >
                        Use This Framework
                    </ButtonSecondary>
                </div>
            </div>
        </div>
    </Card>
    <Card v-else>
        <div class="text-center text-indigo-500">
            No alternative frameworks suggested
        </div>
    </Card>
</template>
