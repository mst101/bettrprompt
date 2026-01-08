<script setup lang="ts">
import { locales, setLocale, type LocaleCode, type LocaleInfo } from '@/i18n';
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page = usePage();
const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const currentLocale = computed(() => (page.props.locale as LocaleCode) || 'en');
const currentLocaleInfo = computed(
    () => locales.find((l) => l.code === currentLocale.value) || locales[0],
);

// Get current path without locale prefix
const currentPath = computed(() => {
    const url = page.url;
    const locale = currentLocale.value;
    // Remove locale prefix if present
    if (url.startsWith(`/${locale}/`)) {
        return url.slice(locale.length + 1);
    }
    if (url === `/${locale}`) {
        return '/';
    }
    return url;
});

async function switchLocale(locale: LocaleInfo) {
    if (locale.code === currentLocale.value) {
        isOpen.value = false;
        return;
    }

    isOpen.value = false;

    // Update client-side i18n
    await setLocale(locale.code);

    // Navigate to the same path with new locale
    const newPath = `/${locale.code}${currentPath.value}`;
    router.visit(newPath, {
        preserveState: true,
        preserveScroll: true,
    });
}

function toggleDropdown() {
    isOpen.value = !isOpen.value;
}

// Handle click outside to close dropdown
function handleClickOutside(event: MouseEvent) {
    if (
        dropdownRef.value &&
        !dropdownRef.value.contains(event.target as Node)
    ) {
        isOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Show only currently supported languages
const supportedLanguages = computed(() =>
    locales.filter((l) =>
        ['en-US', 'en-GB', 'de', 'fr', 'es'].includes(l.code),
    ),
);
</script>

<template>
    <div ref="dropdownRef" class="relative">
        <!-- Trigger Button -->
        <button
            type="button"
            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-indigo-600 transition-colors hover:bg-indigo-50"
            data-testid="language-switcher-button"
            @click="toggleDropdown"
        >
            <span
                class="fi"
                :class="`fi-${currentLocaleInfo.flag}`"
                aria-hidden="true"
            />
            <span class="hidden sm:inline">{{
                currentLocaleInfo.nativeName
            }}</span>
            <svg
                class="h-4 w-4 transition-transform"
                :class="{ 'rotate-180': isOpen }"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                class="origin-top-end absolute end-0 z-50 mt-2 w-56 rounded-lg border border-indigo-200 bg-white shadow-lg"
            >
                <div class="p-2">
                    <button
                        v-for="locale in supportedLanguages"
                        :key="locale.code"
                        class="flex w-full items-center gap-2 rounded px-2 py-1.5 text-start text-sm transition-colors"
                        :class="
                            locale.code === currentLocale
                                ? 'bg-indigo-100 text-indigo-900'
                                : 'text-indigo-600 hover:bg-indigo-50'
                        "
                        :data-testid="`locale-option-${locale.code}`"
                        @click="switchLocale(locale)"
                    >
                        <span
                            class="fi"
                            :class="`fi-${locale.flag}`"
                            aria-hidden="true"
                        />
                        <span>{{ locale.nativeName }}</span>
                        <span
                            v-if="locale.code === currentLocale"
                            class="ms-auto"
                        >
                            <svg
                                class="h-4 w-4 text-indigo-600"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </span>
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
