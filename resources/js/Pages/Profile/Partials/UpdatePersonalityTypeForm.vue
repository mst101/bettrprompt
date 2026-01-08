<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonText from '@/Components/Base/Button/ButtonText.vue';
import CollapsibleSection from '@/Components/Base/CollapsibleSection.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import FormRadio from '@/Components/Base/Form/FormRadio.vue';
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import InputLabel from '@/Components/Base/InputLabel.vue';
import LinkButton from '@/Components/Base/LinkButton.vue';
import LinkText from '@/Components/Base/LinkText.vue';
import ButtonTrash from '@/Components/Common/ButtonTrash.vue';
import { useAlert } from '@/Composables/ui/useAlert';
import { useNotification } from '@/Composables/ui/useNotification';
import { useLocaleRoute } from '@/Composables/useLocaleRoute';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    personalityTypes: Record<string, string>;
    visitorMode?: boolean;
    visitorPersonalityType?: string | null;
    visitorTraitPercentages?: Record<string, number> | null;
    collapsible?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    visitorMode: false,
    visitorPersonalityType: null,
    visitorTraitPercentages: null,
    collapsible: true,
});

const emit = defineEmits<{
    (e: 'saved'): void;
}>();
const page = usePage();
const user = computed(() => page.props.auth?.user);
const { success, error } = useNotification();
const { t } = useI18n({ useScope: 'global' });
const { localeRoute } = useLocaleRoute();

// Load from user data or visitor props
const savedPersonalityType = props.visitorMode
    ? props.visitorPersonalityType
    : user.value?.personalityType;
const parts = savedPersonalityType?.split('-') || ['', ''];
const personalityBase = ref(parts[0] || '');
const identity = ref<'A' | 'T' | ''>((parts[1] as 'A' | 'T' | '') || '');

// Compute full personality type
const fullPersonalityType = computed(() => {
    if (personalityBase.value && identity.value) {
        return `${personalityBase.value}-${identity.value}`;
    }
    return '';
});

const form = useForm({
    name: user.value?.name || '',
    email: user.value?.email || '',
    personalityType: fullPersonalityType.value,
    traitPercentages: (user.value?.traitPercentages ||
        props.visitorTraitPercentages || {
            mind: null,
            energy: null,
            nature: null,
            tactics: null,
            identity: null,
        }) as {
        mind: number | null;
        energy: number | null;
        nature: number | null;
        tactics: number | null;
        identity: number | null;
    },
});

// Persist the CTA only after a successful save in this session
const showTaskCta = ref(false);
const taskCtaButton = ref<InstanceType<typeof Link> | null>(null);

const showTraitPercentages = ref(false);

const personalityTypeOptions = computed(() => {
    return Object.entries(props.personalityTypes).map(([value, label]) => ({
        value,
        label: `${label} (${value})`,
    }));
});

// Update form when personality type changes
watch(fullPersonalityType, (newValue) => {
    form.personalityType = newValue;
});

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
    // If user is authenticated, use profile route regardless of visitorMode prop
    // visitorMode only matters for visitor-only forms
    const isAuthenticated = !!user.value;
    const routeName = isAuthenticated
        ? 'profile.personality.update'
        : 'visitor.personality.update';

    const currentUrl = page.url || '';
    const isOnPromptBuilder = currentUrl.includes('/prompt-builder');

    form.patch(route(routeName), {
        preserveScroll: true,
        onSuccess: async () => {
            success(t('profile.personality.notifications.updated'));
            emit('saved');

            // If we're on the prompt builder, navigate back there instead of profile
            if (isOnPromptBuilder) {
                router.visit(localeRoute('prompt-builder.index'));
            } else if (isAuthenticated) {
                // Show CTA after saving (only for authenticated users on profile page)
                showTaskCta.value = true;
                // Focus the button after it appears
                await nextTick();
                // Get the underlying DOM element from the Link component
                const linkElement = taskCtaButton.value?.$el as HTMLElement;
                linkElement?.focus();
            }
        },
    });
};

const { confirm } = useAlert();

const clearPersonality = async () => {
    const confirmed = await confirm(
        t('profile.personality.clearConfirm.message'),
        t('profile.personality.clearConfirm.title'),
        {
            confirmButtonStyle: 'danger',
            confirmText: t('common.buttons.clear'),
        },
    );

    if (confirmed) {
        const clearForm = useForm({
            personalityType: '',
            traitPercentages: {
                mind: null,
                energy: null,
                nature: null,
                tactics: null,
                identity: null,
            },
        });

        // If user is authenticated, use profile route regardless of visitorMode prop
        const isAuthenticated = !!user.value;
        const routeName = isAuthenticated
            ? 'profile.personality.update'
            : 'visitor.personality.update';

        const currentUrl = page.url || '';
        const isOnPromptBuilder = currentUrl.includes('/prompt-builder');

        clearForm.patch(route(routeName), {
            preserveScroll: true,
            onSuccess: () => {
                personalityBase.value = '';
                identity.value = '';
                form.traitPercentages.mind = null;
                form.traitPercentages.energy = null;
                form.traitPercentages.nature = null;
                form.traitPercentages.tactics = null;
                form.traitPercentages.identity = null;
                success(t('profile.personality.notifications.cleared'));

                // If we're on the prompt builder, navigate back there instead of profile
                if (isOnPromptBuilder) {
                    router.visit(localeRoute('prompt-builder.index'));
                }
            },
            onError: () => {
                error(t('profile.personality.notifications.clearFailed'));
            },
        });
    }
};
</script>

<template>
    <!-- Collapsible version for profile page -->
    <section v-if="collapsible">
        <CollapsibleSection
            :title="$t('profile.personality.title')"
            :subtitle="$t('profile.personality.subtitle')"
            data-testid="personality"
            icon="personality"
        >
            <form class="space-y-6" @submit.prevent="submit">
                <!-- 16personalities Logo Link -->
                <div v-if="!visitorMode" class="mb-4">
                    <a
                        class="block rounded-lg outline-none focus:ring-2 focus:ring-indigo-500"
                        href="https://16personalities.com"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <DynamicIcon
                            name="personalities"
                            class="h-20 rounded-lg px-4 py-3 text-indigo-600 hover:bg-indigo-100"
                        />
                    </a>
                </div>

                <!-- Personality Type Selection -->
                <div class="space-y-4">
                    <FormSelect
                        id="personality-base"
                        v-model="personalityBase"
                        class="max-w-sm"
                        :label="$t('profile.personality.fields.type')"
                        :options="personalityTypeOptions"
                        :error="form.errors.personalityType"
                        :placeholder="
                            $t('profile.personality.placeholders.type')
                        "
                        :autofocus="true"
                    />

                    <!-- Identity Selection -->
                    <div v-if="personalityBase">
                        <InputLabel
                            for="identity"
                            :value="$t('profile.personality.fields.identity')"
                            :required="true"
                        />
                        <div class="mt-2 flex gap-6">
                            <FormRadio
                                id="identity-assertive"
                                v-model="identity"
                                name="identity"
                                value="A"
                                :label="
                                    $t('profile.personality.identity.assertive')
                                "
                                :required="true"
                            />
                            <FormRadio
                                id="identity-turbulent"
                                v-model="identity"
                                name="identity"
                                value="T"
                                :label="
                                    $t('profile.personality.identity.turbulent')
                                "
                                :required="true"
                            />
                        </div>
                    </div>

                    <!-- Selected Personality Display -->
                    <p
                        v-if="fullPersonalityType"
                        class="max-w-sm rounded-md bg-indigo-50 p-2 text-indigo-900 dark:bg-indigo-100"
                    >
                        {{
                            $t('profile.personality.selected', {
                                type: fullPersonalityType,
                            })
                        }}
                    </p>
                </div>

                <!-- Optional Trait Percentages -->
                <div>
                    <ButtonText
                        id="toggle-trait-percentages"
                        type="button"
                        @click="showTraitPercentages = !showTraitPercentages"
                    >
                        {{
                            showTraitPercentages
                                ? $t('profile.personality.traits.toggleHide')
                                : $t('profile.personality.traits.toggleAdd')
                        }}
                        {{ $t('profile.personality.traits.label') }}
                    </ButtonText>

                    <div v-if="showTraitPercentages" class="mt-4 space-y-3">
                        <p class="text-indigo-600">
                            {{ $t('profile.personality.traits.sourcePrefix') }}
                            <LinkText
                                href="https://16personalities.com"
                                target="_blank"
                                rel="noopener noreferrer"
                                >{{
                                    $t('profile.personality.traits.sourceLink')
                                }}</LinkText
                            >.
                        </p>

                        <div class="max-w-xs space-y-4">
                            <div class="relative">
                                <FormInput
                                    id="mind"
                                    v-model="form.traitPercentages.mind"
                                    type="number"
                                    :label="
                                        $t(
                                            'profile.personality.traits.fields.mind',
                                        )
                                    "
                                    :min="50"
                                    :max="100"
                                    class="pr-6 text-right text-lg!"
                                />
                                <span
                                    class="pointer-events-none absolute top-9 right-3 text-lg text-indigo-800"
                                >
                                    %
                                </span>
                            </div>

                            <div class="relative">
                                <FormInput
                                    id="energy"
                                    v-model="form.traitPercentages.energy"
                                    type="number"
                                    :label="
                                        $t(
                                            'profile.personality.traits.fields.energy',
                                        )
                                    "
                                    :min="50"
                                    :max="100"
                                    class="pr-6 text-right text-lg!"
                                />
                                <span
                                    class="pointer-events-none absolute top-9 right-3 text-lg text-indigo-800"
                                >
                                    %
                                </span>
                            </div>

                            <div class="relative">
                                <FormInput
                                    id="nature"
                                    v-model="form.traitPercentages.nature"
                                    type="number"
                                    :label="
                                        $t(
                                            'profile.personality.traits.fields.nature',
                                        )
                                    "
                                    :min="50"
                                    :max="100"
                                    class="pr-6 text-right text-lg!"
                                />
                                <span
                                    class="pointer-events-none absolute top-9 right-3 text-lg text-indigo-800"
                                >
                                    %
                                </span>
                            </div>

                            <div class="relative">
                                <FormInput
                                    id="tactics"
                                    v-model="form.traitPercentages.tactics"
                                    type="number"
                                    :label="
                                        $t(
                                            'profile.personality.traits.fields.tactics',
                                        )
                                    "
                                    :min="50"
                                    :max="100"
                                    class="pr-6 text-right text-lg!"
                                />
                                <span
                                    class="pointer-events-none absolute top-9 right-3 text-lg text-indigo-800"
                                >
                                    %
                                </span>
                            </div>

                            <div class="relative">
                                <FormInput
                                    id="identity-percent"
                                    v-model="form.traitPercentages.identity"
                                    type="number"
                                    :label="
                                        $t(
                                            'profile.personality.traits.fields.identity',
                                        )
                                    "
                                    :min="50"
                                    :max="100"
                                    class="pr-6 text-right text-lg!"
                                />
                                <span
                                    class="pointer-events-none absolute top-9 right-3 text-lg text-indigo-800"
                                >
                                    %
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-4 sm:flex-row">
                    <ButtonPrimary
                        type="submit"
                        :disabled="form.processing"
                        :loading="form.processing"
                        icon="download"
                    >
                        {{ $t('common.buttons.save') }}
                    </ButtonPrimary>

                    <ButtonTrash
                        v-if="personalityBase"
                        :label="$t('common.buttons.clear')"
                        @clear="clearPersonality"
                    />

                    <!-- Persist CTA after a successful save in this session -->
                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <LinkButton
                            v-if="showTaskCta"
                            ref="taskCtaButton"
                            :href="localeRoute('prompt-builder.index')"
                        >
                            {{ $t('profile.personality.actions.enterTask') }}
                            <DynamicIcon name="arrow-right" class="h-4 w-4" />
                        </LinkButton>
                    </Transition>
                </div>
            </form>
        </CollapsibleSection>
    </section>

    <!-- Non-collapsible version for prompt builder -->
    <form v-else class="space-y-6" @submit.prevent="submit">
        <!-- 16personalities Logo Link -->
        <div v-if="!visitorMode" class="mb-4">
            <a
                class="block rounded-lg outline-none focus:ring-2 focus:ring-indigo-500"
                href="https://16personalities.com"
                target="_blank"
                rel="noopener noreferrer"
            >
                <DynamicIcon
                    name="personalities"
                    class="h-20 rounded-lg px-4 py-3 text-indigo-600 hover:bg-indigo-100"
                />
            </a>
        </div>

        <!-- Personality Type Selection -->
        <div class="space-y-4">
            <FormSelect
                id="personality-base"
                v-model="personalityBase"
                class="max-w-sm"
                :label="$t('profile.personality.fields.type')"
                :options="personalityTypeOptions"
                :error="form.errors.personalityType"
                :placeholder="$t('profile.personality.placeholders.type')"
                :autofocus="true"
            />

            <!-- Identity Selection -->
            <div v-if="personalityBase">
                <InputLabel
                    for="identity"
                    :value="$t('profile.personality.fields.identity')"
                    :required="true"
                />
                <div class="mt-2 flex gap-6">
                    <FormRadio
                        id="identity-assertive"
                        v-model="identity"
                        name="identity"
                        value="A"
                        :label="$t('profile.personality.identity.assertive')"
                        :required="true"
                    />
                    <FormRadio
                        id="identity-turbulent"
                        v-model="identity"
                        name="identity"
                        value="T"
                        :label="$t('profile.personality.identity.turbulent')"
                        :required="true"
                    />
                </div>
            </div>

            <!-- Optional Trait Percentages (only these two sections for prompt builder) -->
            <ButtonText
                id="toggle-trait-percentages"
                type="button"
                @click="showTraitPercentages = !showTraitPercentages"
            >
                {{
                    showTraitPercentages
                        ? $t('profile.personality.traits.toggleHide')
                        : $t('profile.personality.traits.toggleAdd')
                }}
                {{ $t('profile.personality.traits.label') }}
            </ButtonText>

            <div v-if="showTraitPercentages" class="mt-4 space-y-3">
                <p class="text-indigo-600">
                    {{ $t('profile.personality.traits.sourcePrefix') }}
                    <LinkText
                        href="https://16personalities.com"
                        target="_blank"
                        rel="noopener noreferrer"
                        >{{
                            $t('profile.personality.traits.sourceLink')
                        }}</LinkText
                    >.
                </p>

                <div class="max-w-xs space-y-4">
                    <div class="relative">
                        <FormInput
                            id="mind"
                            v-model="form.traitPercentages.mind"
                            type="number"
                            :label="
                                $t('profile.personality.traits.fields.mind')
                            "
                            :min="50"
                            :max="100"
                            class="pr-6 text-right text-lg!"
                        />
                        <span
                            class="pointer-events-none absolute top-9 right-3 text-lg text-indigo-800"
                        >
                            %
                        </span>
                    </div>

                    <div class="relative">
                        <FormInput
                            id="energy"
                            v-model="form.traitPercentages.energy"
                            type="number"
                            :label="
                                $t('profile.personality.traits.fields.energy')
                            "
                            :min="50"
                            :max="100"
                            class="pr-6 text-right text-lg!"
                        />
                        <span
                            class="pointer-events-none absolute top-9 right-3 text-lg text-indigo-800"
                        >
                            %
                        </span>
                    </div>

                    <div class="relative">
                        <FormInput
                            id="nature"
                            v-model="form.traitPercentages.nature"
                            type="number"
                            :label="
                                $t('profile.personality.traits.fields.nature')
                            "
                            :min="50"
                            :max="100"
                            class="pr-6 text-right text-lg!"
                        />
                        <span
                            class="pointer-events-none absolute top-9 right-3 text-lg text-indigo-800"
                        >
                            %
                        </span>
                    </div>

                    <div class="relative">
                        <FormInput
                            id="tactics"
                            v-model="form.traitPercentages.tactics"
                            type="number"
                            :label="
                                $t('profile.personality.traits.fields.tactics')
                            "
                            :min="50"
                            :max="100"
                            class="pr-6 text-right text-lg!"
                        />
                        <span
                            class="pointer-events-none absolute top-9 right-3 text-lg text-indigo-800"
                        >
                            %
                        </span>
                    </div>

                    <div class="relative">
                        <FormInput
                            id="identity-percent"
                            v-model="form.traitPercentages.identity"
                            type="number"
                            :label="
                                $t('profile.personality.traits.fields.identity')
                            "
                            :min="50"
                            :max="100"
                            class="pr-6 text-right text-lg!"
                        />
                        <span
                            class="pointer-events-none absolute top-9 right-3 text-lg text-indigo-800"
                        >
                            %
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="flex flex-col items-start gap-3 sm:flex-row sm:items-center"
        >
            <ButtonPrimary
                type="submit"
                :disabled="form.processing || !fullPersonalityType"
                :loading="form.processing"
                icon="download"
            >
                {{ $t('common.buttons.save') }}
            </ButtonPrimary>

            <p v-if="personalityBase && !identity" class="text-sm">
                {{ $t('profile.personality.identity.help') }}
            </p>
        </div>
    </form>
</template>
