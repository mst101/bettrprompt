<script setup lang="ts">
interface Props {
    stage: number;
    title: string;
    description: string;
    bgColour?: string;
    contentBgColour?: string;
}

withDefaults(defineProps<Props>(), {
    bgColour: 'bg-indigo-600',
    contentBgColour: 'bg-indigo-50',
});
</script>

<template>
    <div class="relative">
        <!-- Stage indicator and content -->
        <div class="flex items-start gap-4">
            <!-- Numbered circle -->
            <div
                :class="[
                    bgColour,
                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-lg font-bold text-white',
                ]"
            >
                {{ stage }}
            </div>

            <!-- Stage content -->
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ title }}
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    {{ description }}
                </p>

                <!-- Content panel (example content) -->
                <div
                    :class="[
                        contentBgColour,
                        'mt-3 rounded-lg border border-indigo-200 p-4',
                    ]"
                >
                    <slot />
                </div>
            </div>
        </div>

        <!-- Connecting line to next stage (hidden for stage 5) -->
        <div v-if="stage < 5" class="mt-4 ml-5 h-8 w-0.5 bg-gray-300" />
    </div>
</template>
