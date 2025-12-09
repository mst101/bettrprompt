<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonText from '@/Components/ButtonText.vue';
import FormSelect from '@/Components/FormSelect.vue';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface SelectOption {
    value: string;
    label: string;
}

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
    countries: SelectOption[];
    currencies: SelectOption[];
    languages: SelectOption[];
}

const props = defineProps<Props>();

// Common timezones (still using common ones as not in database)
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

const form = useForm({
    countryCode: props.locationData.countryCode || '',
    timezone: props.locationData.timezone || '',
    currencyCode: props.locationData.currencyCode || '',
    languageCode: props.locationData.languageCode || '',
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
                <ButtonText id="clear-location-form" @click="clearLocation">
                    Clear
                </ButtonText>
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

                <!-- Country -->
                <FormSelect
                    id="countryCode"
                    v-model="form.countryCode"
                    label="Country"
                    :options="props.countries"
                    :error="form.errors.countryCode"
                    placeholder="Select country"
                    show-placeholder
                />

                <!-- Currency -->
                <FormSelect
                    id="currencyCode"
                    v-model="form.currencyCode"
                    label="Currency"
                    :options="props.currencies"
                    :error="form.errors.currencyCode"
                    placeholder="Select currency"
                    show-placeholder
                />

                <!-- Language -->
                <FormSelect
                    id="languageCode"
                    v-model="form.languageCode"
                    label="Language"
                    :options="props.languages"
                    :error="form.errors.languageCode"
                    placeholder="Select language"
                    show-placeholder
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

                <ButtonSecondary
                    type="button"
                    :disabled="form.processing"
                    @click="detectLocation"
                >
                    Auto-Detect
                </ButtonSecondary>
            </div>
        </form>
    </section>
</template>
