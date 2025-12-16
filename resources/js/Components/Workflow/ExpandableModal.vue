<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';

interface Props {
    show: boolean;
    title: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
}>();

const slotContainer = ref<HTMLElement>();
const lineNumbersContainer = ref<HTMLElement>();
const lineCount = ref(0);
const scrollListenerAttached = ref(false);
let cachedTextareaPaddingTop = 0;
let cachedScrollOffset = 0;

const countLines = () => {
    if (!slotContainer.value) return;

    // First, try to find a textarea and count its lines
    const textarea = slotContainer.value.querySelector('textarea');
    if (textarea) {
        const lines = textarea.value.split('\n');
        lineCount.value = lines.length + 20;

        // Cache the padding-top and scroll offset for use in scroll syncing
        if (cachedTextareaPaddingTop === 0) {
            const textareaStyle = window.getComputedStyle(textarea);
            cachedTextareaPaddingTop = parseFloat(textareaStyle.paddingTop);

            // Also cache the scroll offset
            if (lineNumbersContainer.value) {
                const lineNumbersStyle = window.getComputedStyle(
                    lineNumbersContainer.value,
                );
                const lineNumbersMarginTop = parseFloat(
                    lineNumbersStyle.marginTop,
                );
                const lineNumbersPaddingTop = parseFloat(
                    lineNumbersStyle.paddingTop,
                );
                cachedScrollOffset =
                    cachedTextareaPaddingTop -
                    (lineNumbersMarginTop + lineNumbersPaddingTop);
            }
        }
        return;
    }

    // Otherwise count from text content (for pre/code blocks)
    const text = slotContainer.value.textContent || '';
    const lines = text.split('\n');
    lineCount.value = lines.length;
};

const syncScroll = (event: Event) => {
    const textarea = event.target as HTMLTextAreaElement;
    if (lineNumbersContainer.value && cachedScrollOffset !== 0) {
        // Use cached scroll offset for instant syncing
        lineNumbersContainer.value.scrollTop =
            textarea.scrollTop - cachedScrollOffset;
    }
};

const hasTextarea = () => {
    return slotContainer.value?.querySelector('textarea') !== null;
};

const attachScrollListener = () => {
    if (scrollListenerAttached.value) return;

    const textarea = slotContainer.value?.querySelector('textarea');
    if (textarea) {
        textarea.addEventListener('scroll', syncScroll, { passive: true });
        scrollListenerAttached.value = true;
    }
};

onMounted(() => {
    // Set up MutationObserver to watch for content changes
    const observer = new MutationObserver(() => {
        countLines();
    });

    if (slotContainer.value) {
        observer.observe(slotContainer.value, {
            childList: true,
            subtree: true,
            characterData: true,
        });
    }
});

// Watch for when the modal becomes visible
watch(
    () => props.show,
    (isVisible) => {
        if (isVisible) {
            // Modal is now visible, set up everything
            cachedTextareaPaddingTop = 0; // Reset cache
            countLines();

            // Try to attach scroll listener multiple times
            attachScrollListener();

            setTimeout(() => {
                countLines();
                attachScrollListener();
            }, 0);

            setTimeout(() => {
                countLines();
                attachScrollListener();
            }, 100);
        } else {
            // Modal is hidden, reset the flags so they can be recomputed on next open
            scrollListenerAttached.value = false;
            cachedTextareaPaddingTop = 0;
            cachedScrollOffset = 0;
        }
    },
);

// Watch for textarea value changes
watch(
    () => {
        const textarea = slotContainer.value?.querySelector('textarea');
        return textarea?.value;
    },
    () => {
        countLines();
    },
);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-indigo-50 p-4 text-black"
            @click="emit('close')"
        >
            <div
                class="flex h-[90vh] w-[95vw] flex-col rounded-lg bg-white shadow-lg"
                @click.stop
            >
                <div
                    class="flex items-center justify-between border-b bg-indigo-600 px-6 py-4 text-white"
                >
                    <h2 class="text-lg font-semibold">{{ title }}</h2>
                    <button
                        class="rounded px-3 py-1 hover:bg-indigo-700"
                        title="Close"
                        @click="emit('close')"
                    >
                        ✕
                    </button>
                </div>
                <div
                    class="flex flex-1 flex-col gap-0 overflow-hidden bg-indigo-100"
                >
                    <!-- Editor with line numbers - flex row -->
                    <div class="flex flex-1 gap-0 overflow-hidden">
                        <!-- Line numbers column (only show for textareas) -->
                        <div
                            v-if="hasTextarea()"
                            ref="lineNumbersContainer"
                            class="scrollbar-hide flex-shrink-0 overflow-x-hidden overflow-y-scroll border-r border-gray-300 bg-gray-100 pr-4 pl-2 text-right font-mono text-sm text-gray-500 select-none"
                            style="
                                -ms-overflow-style: none;
                                scrollbar-width: none;
                                padding-top: 1.4rem;
                                padding-bottom: 1.5rem;
                                line-height: 1.5;
                            "
                        >
                            <div
                                v-for="n in lineCount"
                                :key="n"
                                style="height: 1.5em"
                            >
                                {{ n }}
                            </div>
                        </div>
                        <!-- Content area - maximize textarea/content -->
                        <div ref="slotContainer" class="flex-1 overflow-auto">
                            <slot />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* Hide scrollbar in line numbers column */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
