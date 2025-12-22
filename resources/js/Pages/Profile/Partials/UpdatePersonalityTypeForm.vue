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
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';

interface Props {
    personalityTypes: Record<string, string>;
    visitorMode?: boolean;
    visitorPersonalityType?: string | null;
    visitorTraitPercentages?: Record<string, number> | null;
}

const props = withDefaults(defineProps<Props>(), {
    visitorMode: false,
    visitorPersonalityType: null,
    visitorTraitPercentages: null,
});

const emit = defineEmits<{
    (e: 'saved'): void;
}>();
const page = usePage();
const user = computed(() => page.props.auth?.user);
const { success, error } = useNotification();

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
    const routeName = props.visitorMode
        ? 'visitor.personality.update'
        : 'profile.personality.update';

    form.patch(route(routeName), {
        preserveScroll: true,
        onSuccess: async () => {
            success('Personality type updated successfully');
            emit('saved');
            if (!props.visitorMode) {
                // Show CTA after saving (only for authenticated users)
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
        'Are you sure you want to clear all personality information?',
        'Clear Personality Information',
        { confirmButtonStyle: 'danger', confirmText: 'Clear' },
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

        const routeName = props.visitorMode
            ? 'visitor.personality.update'
            : 'profile.personality.update';

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
                success('Personality information cleared');
            },
            onError: () => {
                error('Failed to clear personality information');
            },
        });
    }
};
</script>

<template>
    <section>
        <CollapsibleSection
            title="Your Personality Type"
            subtitle="Update your personality type to get more personalised AI prompts."
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
                        label="Personality Type"
                        :options="personalityTypeOptions"
                        :error="form.errors.personalityType"
                        placeholder="Your personality type"
                        :autofocus="true"
                    />

                    <!-- Identity Selection -->
                    <div v-if="personalityBase">
                        <InputLabel
                            for="identity"
                            value="Identity"
                            :required="true"
                        />
                        <div class="mt-2 flex gap-6">
                            <FormRadio
                                id="identity-assertive"
                                v-model="identity"
                                name="identity"
                                value="A"
                                label="Assertive (A)"
                                :required="true"
                            />
                            <FormRadio
                                id="identity-turbulent"
                                v-model="identity"
                                name="identity"
                                value="T"
                                label="Turbulent (T)"
                                :required="true"
                            />
                        </div>
                    </div>

                    <!-- Selected Personality Display -->
                    <p
                        v-if="fullPersonalityType"
                        class="max-w-sm rounded-md bg-indigo-50 p-2 text-indigo-900 dark:bg-indigo-100"
                    >
                        Selected: {{ fullPersonalityType }}
                    </p>
                </div>

                <!-- Optional Trait Percentages -->
                <div>
                    <ButtonText
                        id="toggle-trait-percentages"
                        type="button"
                        @click="showTraitPercentages = !showTraitPercentages"
                    >
                        {{ showTraitPercentages ? '− Hide' : '+ Add' }}
                        Trait Percentages (optional)
                    </ButtonText>

                    <div v-if="showTraitPercentages" class="mt-4 space-y-3">
                        <p class="text-indigo-600">
                            Enter your trait percentages from
                            <LinkText
                                href="https://16personalities.com"
                                target="_blank"
                                rel="noopener noreferrer"
                                >16personalities.com</LinkText
                            >.
                        </p>

                        <div class="max-w-xs space-y-4">
                            <div class="relative">
                                <FormInput
                                    id="mind"
                                    v-model="form.traitPercentages.mind"
                                    type="number"
                                    label="Introversion/Extraversion"
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
                                    label="Intuitive/Observant"
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
                                    label="Thinking/Feeling"
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
                                    label="Judging/Prospecting"
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
                                    label="Assertive/Turbulent"
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
                        Save
                    </ButtonPrimary>

                    <ButtonTrash
                        v-if="personalityBase"
                        label="Clear"
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
                            :href="route('prompt-builder.index')"
                        >
                            Enter your Task
                            <DynamicIcon name="arrow-right" class="h-4 w-4" />
                        </LinkButton>
                    </Transition>
                </div>
            </form>
        </CollapsibleSection>
    </section>
</template>
