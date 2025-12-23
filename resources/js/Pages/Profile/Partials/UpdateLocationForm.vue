<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import CollapsibleSection from '@/Components/Base/CollapsibleSection.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import ButtonTrash from '@/Components/Common/ButtonTrash.vue';
import { useAlert } from '@/Composables/ui/useAlert';
import { useNotification } from '@/Composables/ui/useNotification';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

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
const { success, error } = useNotification();

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
    region: props.locationData.region || '',
    city: props.locationData.city || '',
    timezone: props.locationData.timezone || '',
    currencyCode: props.locationData.currencyCode || '',
    languageCode: props.locationData.languageCode || '',
});

watch(
    () => form.recentlySuccessful,
    (value) => {
        if (value) {
            success('Location updated successfully');
        }
    },
);

watch(
    () => Object.keys(form.errors).length > 0,
    (hasErrors) => {
        if (hasErrors) {
            const errorMessage = Object.values(form.errors)[0];
            if (typeof errorMessage === 'string') {
                error(errorMessage);
            }
        }
    },
);

const submit = () => {
    form.patch(route('profile.location.update'), {
        preserveScroll: true,
    });
};

const detectLocation = () => {
    const detectForm = useForm({});
    detectForm.post(route('profile.location.detect'), {
        preserveScroll: true,
        onSuccess: (page) => {
            success('Location detected successfully');
            // Update form fields with detected location
            const locationData = page.props
                .locationData as typeof props.locationData;
            if (locationData) {
                form.countryCode = locationData.countryCode || '';
                form.region = locationData.region || '';
                form.city = locationData.city || '';
                form.timezone = locationData.timezone || '';
                form.currencyCode = locationData.currencyCode || '';
                form.languageCode = locationData.languageCode || '';
            }
        },
        onError: () => {
            error('Failed to detect location');
        },
    });
};

const { confirm } = useAlert();

const clearLocation = async () => {
    const confirmed = await confirm(
        'Are you sure you want to clear all location data?',
        'Clear Location',
        { confirmButtonStyle: 'danger', confirmText: 'Clear' },
    );

    if (confirmed) {
        const clearForm = useForm({});
        clearForm.delete(route('profile.location.clear'), {
            preserveScroll: true,
            onSuccess: () => {
                success('Location cleared successfully');
                // Clear the form fields
                form.countryCode = '';
                form.region = '';
                form.city = '';
                form.timezone = '';
                form.currencyCode = '';
                form.languageCode = '';
            },
            onError: () => {
                error('Failed to clear location');
            },
        });
    }
};
</script>

<template>
    <CollapsibleSection
        title="Location & Language"
        subtitle="Set your location and language preferences for better optimised AI prompts."
        data-testid="location"
        icon="map-pin"
    >
        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-6 sm:grid-cols-2">
                <!-- Country -->
                <FormSelect
                    id="country-code"
                    v-model="form.countryCode"
                    label="Country"
                    :options="props.countries"
                    :error="form.errors.countryCode"
                    placeholder="Select country"
                    show-placeholder
                />

                <!-- Region -->
                <FormInput
                    id="region"
                    v-model="form.region"
                    label="Region/State"
                    placeholder="e.g., California, Lancashire"
                    :error="form.errors.region"
                />

                <!-- City -->
                <FormInput
                    id="city"
                    v-model="form.city"
                    label="City"
                    placeholder="e.g., San Francisco, London"
                    :error="form.errors.city"
                />

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
                    id="currency-code"
                    v-model="form.currencyCode"
                    label="Currency"
                    :options="props.currencies"
                    :error="form.errors.currencyCode"
                    placeholder="Select currency"
                    show-placeholder
                />

                <!-- Language -->
                <FormSelect
                    id="language-code"
                    v-model="form.languageCode"
                    label="Language"
                    :options="props.languages"
                    :error="form.errors.languageCode"
                    placeholder="Select language"
                    show-placeholder
                />
            </div>

            <div class="flex flex-col items-center gap-4 sm:flex-row">
                <ButtonPrimary
                    type="submit"
                    :disabled="form.processing"
                    :loading="form.processing"
                    icon="download"
                >
                    Save Location
                </ButtonPrimary>

                <ButtonSecondary
                    type="button"
                    :disabled="form.processing"
                    icon="sparkles"
                    @click="detectLocation"
                >
                    Auto-Detect
                </ButtonSecondary>

                <ButtonTrash
                    v-if="locationData.countryName"
                    id="clear-location-form"
                    label="Clear"
                    @clear="clearLocation"
                />
            </div>
        </form>
    </CollapsibleSection>
</template>
