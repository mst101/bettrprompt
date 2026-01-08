<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

export interface CarouselItem {
    id: string;
    icon: string;
    title: string;
    subtitle: string;
    bullets: string[];
}

interface Props {
    items: CarouselItem[];
    modelValue?: number;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: 0,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: number): void;
}>();

const { t } = useI18n({ useScope: 'global' });

const activeIndex = computed({
    get: () => props.modelValue,
    set: (value: number) => emit('update:modelValue', value),
});

const slideDirection = ref<'left' | 'right'>('right');

const goToSlide = (index: number) => {
    if (index === activeIndex.value) return;
    slideDirection.value = index > activeIndex.value ? 'right' : 'left';
    activeIndex.value = index;
};

const nextSlide = () => {
    if (props.items.length === 0) return;
    slideDirection.value = 'right';
    activeIndex.value = (activeIndex.value + 1) % props.items.length;
};

const previousSlide = () => {
    if (props.items.length === 0) return;
    slideDirection.value = 'left';
    activeIndex.value =
        activeIndex.value === 0
            ? props.items.length - 1
            : activeIndex.value - 1;
};

const handleKeydown = (event: KeyboardEvent) => {
    switch (event.key) {
        case 'ArrowLeft':
            event.preventDefault();
            previousSlide();
            break;
        case 'ArrowRight':
            event.preventDefault();
            nextSlide();
            break;
        case 'Home':
            event.preventDefault();
            goToSlide(0);
            break;
        case 'End':
            event.preventDefault();
            goToSlide(props.items.length - 1);
            break;
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});

const currentItem = computed(() => props.items[activeIndex.value]);
</script>

<template>
    <div
        v-if="items.length > 0"
        role="region"
        :aria-label="$t('components.base.carousel.ariaLabel')"
        class="mx-auto max-w-4xl"
    >
        <!-- Carousel with Side Buttons -->
        <div class="flex items-center gap-8">
            <!-- Previous Button (Left Side) -->
            <button
                type="button"
                :aria-label="$t('components.base.carousel.previous')"
                class="shrink-0 rounded-full bg-indigo-50 p-3 text-white transition-colors hover:bg-indigo-500 focus:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-indigo-50 focus:outline-hidden"
                @click="previousSlide"
            >
                <DynamicIcon name="chevron-left" class="size-8" />
            </button>

            <!-- Carousel Content Container -->
            <div class="relative flex-1 overflow-hidden">
                <div class="relative flex h-72 items-start">
                    <TransitionGroup
                        enter-active-class="absolute left-0 right-0 top-0 transition-all duration-300"
                        leave-active-class="absolute left-0 right-0 top-0 transition-all duration-300"
                        :enter-from-class="
                            slideDirection === 'right'
                                ? 'translate-x-full opacity-0'
                                : '-translate-x-full opacity-0'
                        "
                        :leave-to-class="
                            slideDirection === 'right'
                                ? '-translate-x-full opacity-0'
                                : 'translate-x-full opacity-0'
                        "
                    >
                        <div
                            v-if="currentItem"
                            :key="currentItem.id"
                            role="group"
                            :aria-roledescription="
                                $t('components.base.carousel.slideRole')
                            "
                            :aria-label="
                                $t('components.base.carousel.slideLabel', {
                                    current: activeIndex + 1,
                                    total: items.length,
                                })
                            "
                            class="w-full rounded-lg bg-white p-8 shadow-lg dark:bg-indigo-50"
                        >
                            <!-- Icon and Title -->
                            <div class="flex items-center gap-4">
                                <div
                                    class="inline-flex shrink-0 items-center justify-center rounded-lg bg-indigo-100 p-4"
                                >
                                    <DynamicIcon
                                        :name="currentItem.icon"
                                        class="size-8 text-indigo-600"
                                    />
                                </div>
                                <div>
                                    <h3
                                        class="text-2xl font-bold text-indigo-900"
                                    >
                                        {{ currentItem.title }}
                                    </h3>
                                    <!-- Subtitle -->
                                    <p class="text-indigo-700">
                                        {{ currentItem.subtitle }}
                                    </p>
                                </div>
                            </div>

                            <!-- Bullets -->
                            <ul
                                class="mt-4 list-inside list-disc space-y-2 text-indigo-800"
                            >
                                <li
                                    v-for="(
                                        bullet, index
                                    ) in currentItem.bullets"
                                    :key="index"
                                >
                                    {{ bullet }}
                                </li>
                            </ul>
                        </div>
                    </TransitionGroup>
                </div>
            </div>

            <!-- Next Button (Right Side) -->
            <button
                type="button"
                :aria-label="$t('components.base.carousel.next')"
                class="shrink-0 rounded-full bg-indigo-50 p-3 text-white transition-colors hover:bg-indigo-500 focus:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-indigo-50 focus:outline-hidden"
                @click="nextSlide"
            >
                <DynamicIcon name="chevron-right" class="size-8" />
            </button>
        </div>

        <!-- Dot Indicators -->
        <div class="mt-4 flex items-center justify-center">
            <div role="tablist" class="flex gap-4">
                <button
                    v-for="(item, index) in items"
                    :key="item.id"
                    role="tab"
                    :aria-selected="index === activeIndex"
                    :aria-label="
                        $t('components.base.carousel.goToSlide', {
                            index: index + 1,
                        })
                    "
                    class="outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-indigo-50"
                    :class="[
                        'size-2.5 rounded-full transition-all',
                        index === activeIndex
                            ? 'bg-indigo-600'
                            : 'bg-indigo-300 hover:bg-indigo-500 focus:bg-indigo-500',
                    ]"
                    @click="goToSlide(index)"
                />
            </div>
        </div>

        <!-- Live Region for Announcements -->
        <div aria-live="polite" aria-atomic="true" class="sr-only">
            {{
                t('components.base.carousel.liveRegion', {
                    current: activeIndex + 1,
                    total: items.length,
                    title: currentItem?.title || '',
                })
            }}
        </div>
    </div>

    <!-- Empty State -->
    <div v-else class="mx-auto max-w-3xl text-center text-indigo-700">
        <p>{{ $t('components.base.carousel.empty') }}</p>
    </div>
</template>
