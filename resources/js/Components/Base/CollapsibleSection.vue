<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import { ref } from 'vue';

interface Props {
    title: string;
    subtitle: string;
    defaultOpen?: boolean;
}

withDefaults(defineProps<Props>(), {
    defaultOpen: false,
});

const isOpen = ref(false);
</script>

<template>
    <section>
        <!-- Collapsible Header -->
        <button
            type="button"
            class="flex w-full cursor-pointer items-start justify-between gap-4 rounded-t-lg px-4 py-6 text-left transition-colors hover:bg-indigo-50"
            :class="isOpen ? 'bg-indigo-50' : 'rounded-lg bg-white'"
            @click="isOpen = !isOpen"
        >
            <div class="min-w-0 flex-1">
                <h2 class="text-lg font-medium text-indigo-900">
                    {{ title }}
                </h2>

                <p class="mt-1 text-sm text-indigo-600">
                    {{ subtitle }}
                </p>
            </div>

            <!-- Chevron Icon -->
            <DynamicIcon
                name="chevron-down"
                :class="[
                    'mt-1 size-5 shrink-0 text-indigo-600 transition-transform duration-300',
                    isOpen ? 'rotate-180' : '',
                ]"
            />
        </button>

        <!-- Collapsible Content -->
        <Transition
            enter-active-class="overflow-hidden transition-all duration-300"
            leave-active-class="overflow-hidden transition-all duration-300"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-[2000px] opacity-100"
            leave-from-class="max-h-[2000px] opacity-100"
            leave-to-class="max-h-0 opacity-0"
        >
            <div v-if="isOpen" class="rounded-b-lg bg-white px-4 pb-6">
                <slot />
            </div>
        </Transition>
    </section>
</template>
