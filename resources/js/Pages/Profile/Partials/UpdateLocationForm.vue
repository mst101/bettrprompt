<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import CollapsibleSection from '@/Components/Base/CollapsibleSection.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import ButtonTrash from '@/Components/Common/ButtonTrash.vue';
import { useAlert } from '@/Composables/ui/useAlert';
import { useNotification } from '@/Composables/ui/useNotification';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import { setLocale, type LocaleCode } from '@/i18n';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { useI18n } from 'vue-i18n';

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
    countryDefaults: Record<
        string,
        {
            currencyCode: string;
            languageCode: string;
        }
    >;
}

const props = defineProps<Props>();
const page = usePage();
const { success, error } = useNotification();
const { t } = useI18n({ useScope: 'global' });
const { countryRoute, currentCountry } = useCountryRoute();
const currentLocale = page.props.locale as LocaleCode;

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

const getBrowserTimezone = (): string | null => {
    try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone || null;
    } catch {
        return null;
    }
};

watch(
    () => form.countryCode,
    (newCountry, oldCountry) => {
        if (!newCountry || newCountry === oldCountry) {
            return;
        }

        form.region = '';
        form.city = '';

        const defaults = props.countryDefaults[newCountry];
        if (defaults?.currencyCode) {
            form.currencyCode = defaults.currencyCode;
        }
        if (defaults?.languageCode) {
            form.languageCode = defaults.languageCode;
        }

        if (!form.timezone) {
            const browserTimezone = getBrowserTimezone();
            if (browserTimezone) {
                form.timezone = browserTimezone;
            }
        }
    },
);

watch(
    () => props.locationData.languageCode,
    (newLanguageCode) => {
        // Update form when language changes from LanguageSwitcher
        if (newLanguageCode && newLanguageCode !== form.languageCode) {
            form.languageCode = newLanguageCode;
        }
    },
);

watch(
    () => form.recentlySuccessful,
    (value) => {
        if (value) {
            success(t('profile.location.notifications.updated'));
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

const submit = async () => {
    // Check if language is changing
    const languageChanged =
        form.languageCode &&
        form.languageCode !== props.locationData.languageCode;
    const newLocale = form.languageCode as LocaleCode;
    const targetCountry = form.countryCode || currentCountry.value;

    if (languageChanged) {
        // Update frontend i18n first
        await setLocale(newLocale);

        // Then submit the form, which will redirect to the new locale
        form.patch(countryRoute('profile.location.update'), {
            preserveScroll: true,
            onSuccess: () => {
                // Navigate to new country's profile page
                // Note: Do NOT use preserveState: true here, as it would preserve the old page props
                // and prevent the updated locale from being reflected in the UI
                router.visit(`/${targetCountry}/profile`, {
                    preserveScroll: true,
                });
            },
        });
    } else {
        // No language change, just submit normally
        form.patch(countryRoute('profile.location.update'), {
            preserveScroll: true,
            onSuccess: () => {
                if (targetCountry !== currentCountry.value) {
                    router.visit(`/${targetCountry}/profile`, {
                        preserveScroll: true,
                    });
                }
            },
        });
    }
};

const detectLocation = async () => {
    const detectForm = useForm({});
    detectForm.post(countryRoute('profile.location.detect'), {
        preserveScroll: true,
        onSuccess: async (page) => {
            success(t('profile.location.notifications.detected'));
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

                // If country was detected and changed, navigate to the new country's profile page
                if (
                    locationData.countryCode &&
                    locationData.countryCode !== currentCountry.value
                ) {
                    // If language also changed, update frontend i18n first
                    if (
                        locationData.languageCode &&
                        locationData.languageCode !== currentLocale
                    ) {
                        const newLocale =
                            locationData.languageCode as LocaleCode;
                        await setLocale(newLocale);
                    }
                    // Navigate to the new country's profile page
                    router.visit(`/${locationData.countryCode}/profile`, {
                        preserveScroll: true,
                    });
                } else if (
                    // If only language changed (same country), update frontend i18n and refresh
                    locationData.languageCode &&
                    locationData.languageCode !== currentLocale
                ) {
                    const newLocale = locationData.languageCode as LocaleCode;
                    await setLocale(newLocale);

                    // Refresh page to get updated locale in Inertia props
                    router.visit(`/${currentCountry.value}/profile`, {
                        preserveScroll: true,
                    });
                }
            }
        },
        onError: () => {
            error(t('profile.location.notifications.detectFailed'));
        },
    });
};

const { confirm } = useAlert();

const clearLocation = async () => {
    const confirmed = await confirm(
        t('profile.location.clearConfirm.message'),
        t('profile.location.clearConfirm.title'),
        {
            confirmButtonStyle: 'danger',
            confirmText: t('common.buttons.clear'),
        },
    );

    if (confirmed) {
        const clearForm = useForm({});
        clearForm.delete(countryRoute('profile.location.clear'), {
            preserveScroll: true,
            onSuccess: () => {
                success(t('profile.location.notifications.cleared'));
                // Clear the form fields
                form.countryCode = '';
                form.region = '';
                form.city = '';
                form.timezone = '';
                form.currencyCode = '';
                form.languageCode = '';
            },
            onError: () => {
                error(t('profile.location.notifications.clearFailed'));
            },
        });
    }
};
</script>

<template>
    <CollapsibleSection
        :title="$t('profile.location.title')"
        :subtitle="$t('profile.location.subtitle')"
        data-testid="location"
        icon="map-pin"
    >
        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-6 sm:grid-cols-2">
                <!-- Country -->
                <FormSelect
                    id="country-code"
                    v-model="form.countryCode"
                    :label="$t('profile.location.fields.country')"
                    :options="props.countries"
                    :error="form.errors.countryCode"
                    :placeholder="$t('profile.location.placeholders.country')"
                    show-placeholder
                />

                <!-- Region -->
                <FormInput
                    id="region"
                    v-model="form.region"
                    :label="$t('profile.location.fields.region')"
                    :placeholder="$t('profile.location.placeholders.region')"
                    :error="form.errors.region"
                />

                <!-- City -->
                <FormInput
                    id="city"
                    v-model="form.city"
                    :label="$t('profile.location.fields.city')"
                    :placeholder="$t('profile.location.placeholders.city')"
                    :error="form.errors.city"
                />

                <!-- Timezone -->
                <FormSelect
                    id="timezone"
                    v-model="form.timezone"
                    :label="$t('profile.location.fields.timezone')"
                    :options="timezones"
                    :error="form.errors.timezone"
                    :placeholder="$t('profile.location.placeholders.timezone')"
                    show-placeholder
                />

                <!-- Currency -->
                <FormSelect
                    id="currency-code"
                    v-model="form.currencyCode"
                    :label="$t('profile.location.fields.currency')"
                    :options="props.currencies"
                    :error="form.errors.currencyCode"
                    :placeholder="$t('profile.location.placeholders.currency')"
                    show-placeholder
                />

                <!-- Language -->
                <FormSelect
                    id="language-code"
                    v-model="form.languageCode"
                    :label="$t('profile.location.fields.language')"
                    :options="props.languages"
                    :error="form.errors.languageCode"
                    :placeholder="$t('profile.location.placeholders.language')"
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
                    {{ $t('profile.location.actions.save') }}
                </ButtonPrimary>

                <ButtonSecondary
                    type="button"
                    :disabled="form.processing"
                    icon="sparkles"
                    @click="detectLocation"
                >
                    {{ $t('profile.location.actions.detect') }}
                </ButtonSecondary>

                <ButtonTrash
                    v-if="locationData.countryName"
                    id="clear-location-form"
                    :label="$t('common.buttons.clear')"
                    @clear="clearLocation"
                />
            </div>
        </form>
    </CollapsibleSection>
</template>
