<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        align?: 'left' | 'right';
        width?: '48';
        contentClasses?: string;
    }>(),
    {
        align: 'right',
        width: '48',
        contentClasses: 'py-0 bg-white',
    },
);

defineExpose({
    close: () => {
        open.value = false;
    },
});

const closeOnEscape = (e: KeyboardEvent) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const widthClass = computed(() => {
    return {
        48: 'w-48',
    }[props.width.toString()];
});

const alignmentClasses = computed(() => {
    if (props.align === 'left') {
        return 'ltr:origin-top-left rtl:origin-top-right start-0';
    } else if (props.align === 'right') {
        return 'ltr:origin-top-right rtl:origin-top-left end-0';
    } else {
        return 'origin-top';
    }
});

const open = ref(false);
const dropdownContent = ref<HTMLElement | null>(null);
const trigger = ref<HTMLElement | null>(null);
const dropdownStyle = ref<Record<string, string>>({});

// Calculate dropdown position
const updateDropdownPosition = () => {
    if (!trigger.value) return;

    const rect = trigger.value.getBoundingClientRect();
    const styles: Record<string, string> = {
        top: `${rect.bottom}px`,
    };

    if (props.align === 'left') {
        styles.left = `${rect.left}px`;
    } else if (props.align === 'right') {
        styles.right = `${window.innerWidth - rect.right}px`;
    }

    dropdownStyle.value = styles;
};

// Focus first item when dropdown opens
watch(open, async (isOpen) => {
    if (isOpen) {
        updateDropdownPosition();
        await nextTick();
        if (dropdownContent.value) {
            const firstFocusable =
                dropdownContent.value.querySelector<HTMLElement>(
                    'a, button, [tabindex]:not([tabindex="-1"])',
                );
            firstFocusable?.focus();
        }
    }
});
</script>

<template>
    <div class="relative">
        <div
            ref="trigger"
            role="button"
            tabindex="0"
            :aria-expanded="open"
            aria-haspopup="true"
            @click="open = !open"
            @keydown.enter="open = !open"
            @keydown.space="open = !open"
        >
            <slot name="trigger" />
        </div>

        <Teleport to="body">
            <!-- Full Screen Dropdown Overlay -->
            <div
                v-show="open"
                class="fixed inset-0 z-40"
                @click="open = false"
            ></div>

            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-show="open"
                    class="fixed z-50 mt-2 rounded-md shadow-lg"
                    :class="[widthClass, alignmentClasses]"
                    :style="dropdownStyle"
                    @click.stop
                >
                    <div
                        ref="dropdownContent"
                        class="ring-opacity-5 rounded-md ring-1 ring-black"
                        :class="contentClasses"
                    >
                        <slot name="content" />
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
