<script setup lang="ts">
import CollapsibleSection from '@/Components/Base/CollapsibleSection.vue';
import FormRadio from '@/Components/Base/Form/FormRadio.vue';
import InputLabel from '@/Components/Base/InputLabel.vue';
import { useFormWithNotifications } from '@/Composables/data/useFormWithNotifications';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import { watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    uiComplexity: 'simple' | 'advanced';
}

const props = defineProps<Props>();
const { t } = useI18n({ useScope: 'global' });
const { countryRoute } = useCountryRoute();

const form = useFormWithNotifications(
    {
        uiComplexity: props.uiComplexity,
    },
    { successMessage: t('profile.uiComplexity.notifications.updated') },
);

// Auto-save when the selected option changes
watch(
    () => form.uiComplexity,
    () => {
        form.patch(countryRoute('profile.ui-complexity.update'), {
            preserveScroll: true,
        });
    },
);
</script>

<template>
    <section>
        <CollapsibleSection
            :title="$t('profile.uiComplexity.title')"
            :subtitle="$t('profile.uiComplexity.subtitle')"
            data-testid="ui-complexity"
            icon="cog"
        >
            <div class="space-y-6">
                <div>
                    <InputLabel
                        for="uiComplexity"
                        :value="$t('profile.uiComplexity.fieldLabel')"
                    />
                    <div class="mt-3 space-y-3">
                        <FormRadio
                            id="ui-simple"
                            v-model="form.uiComplexity"
                            name="uiComplexity"
                            value="simple"
                            :label="$t('profile.uiComplexity.options.simple')"
                            :help-text="$t('profile.uiComplexity.help.simple')"
                        />

                        <FormRadio
                            id="ui-advanced"
                            v-model="form.uiComplexity"
                            name="uiComplexity"
                            value="advanced"
                            :label="$t('profile.uiComplexity.options.advanced')"
                            :help-text="
                                $t('profile.uiComplexity.help.advanced')
                            "
                        />
                    </div>
                </div>
            </div>
        </CollapsibleSection>
    </section>
</template>
