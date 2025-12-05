<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    locationData: {
        countryCode: string | null;
        countryName: string | null;
        region: string | null;
        city: string | null;
        timezone: string | null;
        currencyCode: string | null;
        languageCode: string | null;
        detectedAt: string | null;
        manuallySet: boolean;
    };
}

const props = defineProps<Props>();

// Common timezones
const timezones = [
    { value: 'UTC', label: 'UTC' },
    { value: 'Europe/London', label: 'Europe/London (GMT/BST)' },
    { value: 'Europe/Paris', label: 'Europe/Paris (CET/CEST)' },
    { value: 'Europe/Berlin', label: 'Europe/Berlin (CET/CEST)' },
    { value: 'America/New_York', label: 'America/New_York (EST/EDT)' },
    { value: 'America/Los_Angeles', label: 'America/Los_Angeles (PST/PDT)' },
    { value: 'America/Chicago', label: 'America/Chicago (CST/CDT)' },
    { value: 'Asia/Tokyo', label: 'Asia/Tokyo (JST)' },
    { value: 'Asia/Shanghai', label: 'Asia/Shanghai (CST)' },
    { value: 'Asia/Hong_Kong', label: 'Asia/Hong_Kong (HKT)' },
    { value: 'Asia/Singapore', label: 'Asia/Singapore (SGT)' },
    { value: 'Asia/Dubai', label: 'Asia/Dubai (GST)' },
    { value: 'Australia/Sydney', label: 'Australia/Sydney (AEDT/AEST)' },
    { value: 'Australia/Melbourne', label: 'Australia/Melbourne (AEDT/AEST)' },
    { value: 'Pacific/Auckland', label: 'Pacific/Auckland (NZDT/NZST)' },
];

// Common currencies
const currencies = [
    { value: 'USD', label: 'US Dollar (USD)' },
    { value: 'EUR', label: 'Euro (EUR)' },
    { value: 'GBP', label: 'British Pound (GBP)' },
    { value: 'JPY', label: 'Japanese Yen (JPY)' },
    { value: 'CAD', label: 'Canadian Dollar (CAD)' },
    { value: 'AUD', label: 'Australian Dollar (AUD)' },
    { value: 'NZD', label: 'New Zealand Dollar (NZD)' },
    { value: 'CHF', label: 'Swiss Franc (CHF)' },
    { value: 'CNY', label: 'Chinese Yuan (CNY)' },
    { value: 'INR', label: 'Indian Rupee (INR)' },
    { value: 'SGD', label: 'Singapore Dollar (SGD)' },
    { value: 'HKD', label: 'Hong Kong Dollar (HKD)' },
    { value: 'BRL', label: 'Brazilian Real (BRL)' },
    { value: 'ZAR', label: 'South African Rand (ZAR)' },
];

// Common languages
const languages = [
    { value: 'en', label: 'English' },
    { value: 'de', label: 'Deutsch (German)' },
    { value: 'fr', label: 'Français (French)' },
    { value: 'es', label: 'Español (Spanish)' },
    { value: 'it', label: 'Italiano (Italian)' },
    { value: 'nl', label: 'Nederlands (Dutch)' },
    { value: 'pt', label: 'Português (Portuguese)' },
    { value: 'ja', label: '日本語 (Japanese)' },
    { value: 'zh', label: '中文 (Chinese)' },
    { value: 'ru', label: 'Русский (Russian)' },
    { value: 'ko', label: '한국어 (Korean)' },
    { value: 'sv', label: 'Svenska (Swedish)' },
    { value: 'no', label: 'Norsk (Norwegian)' },
    { value: 'da', label: 'Dansk (Danish)' },
];

const form = useForm({
    country_code: props.locationData.countryCode || '',
    timezone: props.locationData.timezone || '',
    currency_code: props.locationData.currencyCode || '',
    language_code: props.locationData.languageCode || '',
});

const detectedAtFormatted = computed(() => {
    if (!props.locationData.detectedAt) return null;
    return new Date(props.locationData.detectedAt).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const locationSummary = computed(() => {
    if (!props.locationData.countryName) return 'No location detected';
    return props.locationData.countryName;
});

const submit = () => {
    form.patch(route('profile.location.update'), {
        preserveScroll: true,
    });
};

const detectLocation = () => {
    useForm({}).post(route('profile.location.detect'), {
        preserveScroll: true,
    });
};

const clearLocation = () => {
    if (confirm('Are you sure you want to clear all location data?')) {
        useForm({}).delete(route('profile.location.clear'), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Location & Language
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Set your location and language preferences for better optimised
                AI prompts.
            </p>
        </header>

        <!-- Location Status -->
        <div
            v-if="locationData.countryName"
            class="mt-6 rounded-md bg-blue-50 p-4"
        >
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-900">
                        Current Location
                    </p>
                    <p class="mt-1 text-sm text-blue-700">
                        {{ locationSummary }}
                    </p>
                    <p
                        v-if="detectedAtFormatted"
                        class="mt-1 text-xs text-blue-600"
                    >
                        Detected {{ detectedAtFormatted }}
                    </p>
                </div>
                <button
                    type="button"
                    class="text-xs font-medium text-blue-600 hover:text-blue-700"
                    @click="clearLocation"
                >
                    Clear
                </button>
            </div>
        </div>

        <form class="mt-6 space-y-6" @submit.prevent="submit">
            <div class="grid gap-6 sm:grid-cols-2">
                <!-- Timezone -->
                <FormSelect
                    id="timezone"
                    v-model="form.timezone"
                    label="Timezone"
                    :options="timezones"
                    :error="form.errors.timezone"
                    placeholder="Select timezone"
                    show-placeholder
                />

                <!-- Currency -->
                <FormSelect
                    id="currency_code"
                    v-model="form.currency_code"
                    label="Currency"
                    :options="currencies"
                    :error="form.errors.currency_code"
                    placeholder="Select currency"
                    show-placeholder
                />

                <!-- Language -->
                <FormSelect
                    id="language_code"
                    v-model="form.language_code"
                    label="Language"
                    :options="languages"
                    :error="form.errors.language_code"
                    placeholder="Select language"
                    show-placeholder
                />

                <!-- Country Code (for reference) -->
                <FormInput
                    id="country_code"
                    v-model="form.country_code"
                    label="Country Code"
                    placeholder="e.g., GB, US"
                    :error="form.errors.country_code"
                    help-text="ISO 3166-1 alpha-2 code"
                />
            </div>

            <div class="flex items-center gap-4">
                <ButtonPrimary
                    type="submit"
                    :disabled="form.processing"
                    :loading="form.processing"
                >
                    Save Location
                </ButtonPrimary>

                <button
                    type="button"
                    class="inline-flex items-center rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 disabled:opacity-50"
                    :disabled="form.processing"
                    @click="detectLocation"
                >
                    Auto-Detect
                </button>
            </div>
        </form>
    </section>
</template>
