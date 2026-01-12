<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormCheckbox from '@/Components/Base/Form/FormCheckbox.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import Modal from '@/Components/Base/Modal/Modal.vue';
import ButtonTrash from '@/Components/Common/ButtonTrash.vue';
import { useAlert } from '@/Composables/ui/useAlert';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface SelectOption {
    value: string;
    label: string;
}

interface LocationData {
    countryCode: string | null;
    countryName: string | null;
    region: string | null;
    city: string | null;
    timezone: string | null;
    currencyCode: string | null;
    languageCode: string | null;
    detectedAt: string | null;
    manuallySet: boolean;
}

interface Props {
    show: boolean;
    locationData: LocationData;
    countries: SelectOption[];
    currencies: SelectOption[];
    languages: SelectOption[];
    isAuthenticated: boolean;
    reasonText?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'continue', payload: { dontAskAgain: boolean }): void;
    (
        e: 'updated',
        payload: {
            dontAskAgain: boolean;
            countryCode: string;
        },
    ): void;
}>();

const { countryRoute } = useCountryRoute();
const isSubmitting = ref(false);
const dontAskAgain = ref(false);
const { confirm } = useAlert();
const { t } = useI18n({ useScope: 'global' });

const form = reactive({
    countryCode: props.locationData.countryCode || '',
    region: props.locationData.region || '',
    city: props.locationData.city || '',
    timezone: props.locationData.timezone || '',
    currencyCode: props.locationData.currencyCode || '',
    languageCode: props.locationData.languageCode || '',
});

const errors = reactive<Record<string, string>>({});

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

const updateEndpoint = computed(() =>
    props.isAuthenticated
        ? countryRoute('profile.location.update')
        : countryRoute('visitor.location.update'),
);

const clearEndpoint = computed(() =>
    props.isAuthenticated
        ? countryRoute('profile.location.clear')
        : countryRoute('visitor.location.clear'),
);

const resetForm = () => {
    form.countryCode = props.locationData.countryCode || '';
    form.region = props.locationData.region || '';
    form.city = props.locationData.city || '';
    form.timezone = props.locationData.timezone || '';
    form.currencyCode = props.locationData.currencyCode || '';
    form.languageCode = props.locationData.languageCode || '';
    dontAskAgain.value = false;
    Object.keys(errors).forEach((key) => {
        delete errors[key];
    });
};

watch(
    () => props.show,
    (show) => {
        if (show) {
            resetForm();
        }
    },
);

const setErrors = (payload: Record<string, string[] | string>) => {
    Object.keys(errors).forEach((key) => {
        delete errors[key];
    });

    Object.entries(payload).forEach(([key, value]) => {
        errors[key] = Array.isArray(value) ? value[0] : value;
    });
};

const submit = async () => {
    isSubmitting.value = true;
    try {
        await axios.patch(
            updateEndpoint.value,
            {
                country_code: form.countryCode || null,
                region: form.region || null,
                city: form.city || null,
                timezone: form.timezone || null,
                currency_code: form.currencyCode || null,
                language_code: form.languageCode || null,
            },
            {
                headers: {
                    Accept: 'application/json',
                },
            },
        );

        emit('updated', {
            dontAskAgain: dontAskAgain.value,
            countryCode: form.countryCode,
        });
    } catch (error: any) {
        if (error?.response?.status === 422) {
            setErrors(error.response.data.errors || {});
        }
    } finally {
        isSubmitting.value = false;
    }
};

const clearLocation = async () => {
    const confirmed = await confirm(
        t('promptBuilder.locationPrompt.clearConfirm.message'),
        t('promptBuilder.locationPrompt.clearConfirm.title'),
        {
            confirmButtonStyle: 'danger',
            confirmText: t('promptBuilder.locationPrompt.actions.clear'),
        },
    );

    if (!confirmed) {
        return;
    }

    isSubmitting.value = true;
    try {
        await axios.delete(clearEndpoint.value, {
            headers: {
                Accept: 'application/json',
            },
        });

        form.countryCode = '';
        form.region = '';
        form.city = '';
        form.timezone = '';
        form.currencyCode = '';
        form.languageCode = '';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Modal :show="show" max-width="lg" @close="emit('close')">
        <div class="p-6">
            <div class="flex items-start gap-3">
                <DynamicIcon
                    name="map-pin"
                    class="mt-0.5 h-6 w-6 shrink-0 text-indigo-600"
                />
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-indigo-900">
                        {{ $t('promptBuilder.locationPrompt.title') }}
                    </h3>
                    <p v-if="reasonText" class="mt-2 text-indigo-700">
                        {{ reasonText }}
                    </p>
                    <p class="mt-2 text-indigo-700">
                        {{ $t('promptBuilder.locationPrompt.description') }}
                    </p>
                    <form class="mt-6 space-y-6" @submit.prevent="submit">
                        <div class="grid gap-6 sm:grid-cols-2">
                            <FormSelect
                                id="prompt-location-country"
                                v-model="form.countryCode"
                                :label="$t('profile.location.fields.country')"
                                :options="countries"
                                :error="errors.country_code"
                                :placeholder="
                                    $t('profile.location.placeholders.country')
                                "
                                show-placeholder
                            />

                            <FormInput
                                id="prompt-location-region"
                                v-model="form.region"
                                :label="$t('profile.location.fields.region')"
                                :placeholder="
                                    $t('profile.location.placeholders.region')
                                "
                                :error="errors.region"
                            />

                            <FormInput
                                id="prompt-location-city"
                                v-model="form.city"
                                :label="$t('profile.location.fields.city')"
                                :placeholder="
                                    $t('profile.location.placeholders.city')
                                "
                                :error="errors.city"
                            />

                            <FormSelect
                                id="prompt-location-timezone"
                                v-model="form.timezone"
                                :label="$t('profile.location.fields.timezone')"
                                :options="timezones"
                                :error="errors.timezone"
                                :placeholder="
                                    $t('profile.location.placeholders.timezone')
                                "
                                show-placeholder
                            />

                            <FormSelect
                                id="prompt-location-currency"
                                v-model="form.currencyCode"
                                :label="$t('profile.location.fields.currency')"
                                :options="currencies"
                                :error="errors.currency_code"
                                :placeholder="
                                    $t('profile.location.placeholders.currency')
                                "
                                show-placeholder
                            />

                            <FormSelect
                                id="prompt-location-language"
                                v-model="form.languageCode"
                                :label="$t('profile.location.fields.language')"
                                :options="languages"
                                :error="errors.language_code"
                                :placeholder="
                                    $t('profile.location.placeholders.language')
                                "
                                show-placeholder
                            />
                        </div>

                        <p class="text-sm text-indigo-600">
                            {{
                                $t('promptBuilder.locationPrompt.detectedNote')
                            }}
                        </p>

                        <div
                            class="flex flex-col gap-4 lg:grid lg:grid-cols-[1fr_auto] lg:items-center"
                        >
                            <FormCheckbox
                                id="prompt-location-dismiss"
                                v-model="dontAskAgain"
                                :label="
                                    $t('promptBuilder.locationPrompt.dismiss')
                                "
                            />

                            <div class="flex flex-wrap gap-3 lg:justify-end">
                                <ButtonSecondary
                                    type="button"
                                    :disabled="isSubmitting"
                                    @click="emit('continue', { dontAskAgain })"
                                >
                                    {{
                                        $t(
                                            'promptBuilder.locationPrompt.actions.continue',
                                        )
                                    }}
                                </ButtonSecondary>

                                <ButtonPrimary
                                    type="submit"
                                    :disabled="isSubmitting"
                                    :loading="isSubmitting"
                                    icon="download"
                                >
                                    {{
                                        $t(
                                            'promptBuilder.locationPrompt.actions.save',
                                        )
                                    }}
                                </ButtonPrimary>

                                <ButtonTrash
                                    id="prompt-location-clear"
                                    :label="
                                        $t(
                                            'promptBuilder.locationPrompt.actions.clear',
                                        )
                                    "
                                    :disabled="isSubmitting"
                                    @clear="clearLocation"
                                />
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Modal>
</template>
