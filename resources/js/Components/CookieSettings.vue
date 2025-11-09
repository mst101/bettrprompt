<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
import { useCookieConsent } from '@/Composables/useCookieConsent';
import { COOKIE_CATEGORIES, type CookiePreferences } from '@/constants/cookies';
import { Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Props {
    show: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
}>();

const { cookiePreferences, savePreferences, acceptAll, rejectAll } =
    useCookieConsent();

// Local state for form
const preferences = ref<CookiePreferences>({
    essential: true,
    functional: cookiePreferences.value?.functional ?? false,
    analytics: cookiePreferences.value?.analytics ?? false,
});

// Sync with stored preferences when modal opens
watch(
    () => props.show,
    (isShown) => {
        if (isShown && cookiePreferences.value) {
            preferences.value = {
                essential: true,
                functional: cookiePreferences.value.functional,
                analytics: cookiePreferences.value.analytics,
            };
        }
    },
);

const handleSave = () => {
    savePreferences(preferences.value);
    emit('close');
};

const handleAcceptAll = () => {
    acceptAll();
    emit('close');
};

const handleRejectAll = () => {
    rejectAll();
    emit('close');
};

const functionalEnabled = computed({
    get: () => preferences.value.functional,
    set: (value) => {
        preferences.value.functional = value;
    },
});

const analyticsEnabled = computed({
    get: () => preferences.value.analytics,
    set: (value) => {
        preferences.value.analytics = value;
    },
});
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="2xl">
        <div class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        Cookie Settings
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Manage your cookie preferences. You can enable or disable different types of cookies below.
                        <Link
                            :href="route('cookies')"
                            class="text-indigo-600 hover:text-indigo-800"
                        >
                            Learn more about our cookies
                        </Link>
                    </p>
                </div>
            </div>

            <div class="mt-6 space-y-6">
                <!-- Essential Cookies -->
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-900">
                                {{ COOKIE_CATEGORIES.essential.name }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ COOKIE_CATEGORIES.essential.description }}
                            </p>
                        </div>
                        <div class="ml-4">
                            <span
                                class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800"
                            >
                                Always Active
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Functional Cookies -->
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-900">
                                {{ COOKIE_CATEGORIES.functional.name }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ COOKIE_CATEGORIES.functional.description }}
                            </p>
                        </div>
                        <div class="ml-4">
                            <ToggleSwitch
                                v-model="functionalEnabled"
                                label="Functional cookies"
                            />
                        </div>
                    </div>
                </div>

                <!-- Analytics Cookies -->
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-900">
                                {{ COOKIE_CATEGORIES.analytics.name }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ COOKIE_CATEGORIES.analytics.description }}
                            </p>
                        </div>
                        <div class="ml-4">
                            <ToggleSwitch
                                v-model="analyticsEnabled"
                                label="Analytics cookies"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-end">
                <button
                    @click="handleRejectAll"
                    type="button"
                    class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Reject All
                </button>
                <button
                    @click="handleSave"
                    type="button"
                    class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Save Preferences
                </button>
                <button
                    @click="handleAcceptAll"
                    type="button"
                    class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Accept All
                </button>
            </div>
        </div>
    </Modal>
</template>
