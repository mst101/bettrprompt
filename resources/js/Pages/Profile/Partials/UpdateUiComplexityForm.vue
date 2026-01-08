<script setup lang="ts">
import CollapsibleSection from '@/Components/Base/CollapsibleSection.vue';
import FormRadio from '@/Components/Base/Form/FormRadio.vue';
import InputLabel from '@/Components/Base/InputLabel.vue';
import { useNotification } from '@/Composables/ui/useNotification';
import { useLocaleRoute } from '@/Composables/useLocaleRoute';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    uiComplexity: 'simple' | 'advanced';
}

const props = defineProps<Props>();
const { success, error } = useNotification();
const { t } = useI18n({ useScope: 'global' });
const { localeRoute } = useLocaleRoute();

const form = useForm({
    uiComplexity: props.uiComplexity,
});

watch(
    () => form.recentlySuccessful,
    (value) => {
        if (value) {
            success(t('profile.uiComplexity.notifications.updated'));
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

// Auto-save when the selected option changes
watch(
    () => form.uiComplexity,
    () => {
        form.patch(localeRoute('profile.ui-complexity.update'), {
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
