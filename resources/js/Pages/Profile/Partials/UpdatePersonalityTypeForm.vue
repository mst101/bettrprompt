<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonText from '@/Components/Base/Button/ButtonText.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import FormRadio from '@/Components/Base/Form/FormRadio.vue';
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import InputLabel from '@/Components/Base/InputLabel.vue';
import LinkButton from '@/Components/Base/LinkButton.vue';
import LinkText from '@/Components/Base/LinkText.vue';
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

const submit = () => {
    const routeName = props.visitorMode
        ? 'visitor.personality.update'
        : 'profile.personality.update';

    form.patch(route(routeName), {
        preserveScroll: true,
        onSuccess: async () => {
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
</script>

<template>
    <section>
        <header
            class="flex flex-col items-start sm:flex-row sm:justify-between"
        >
            <div>
                <h2 class="text-lg font-medium text-indigo-900">
                    Your Personality Type
                </h2>

                <p class="mt-1 text-sm text-indigo-600">
                    Update your personality type to get more personalised AI
                    prompts.
                </p>
            </div>

            <div v-if="!visitorMode" class="-ml-4 sm:ml-4">
                <a
                    class="block rounded-lg outline-none focus:ring-2 focus:ring-indigo-500"
                    href="https://16personalities.com"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <DynamicIcon
                        name="personalities"
                        class="my-2 h-16 w-full rounded-lg px-4 py-3 text-indigo-600 hover:bg-indigo-100 sm:mt-0 sm:h-fit sm:w-80"
                    />
                </a>
            </div>
        </header>

        <form class="mt-2 space-y-6" @submit.prevent="submit">
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
                    :required="true"
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
                <div
                    v-if="fullPersonalityType"
                    class="rounded-md bg-indigo-50 p-3"
                >
                    <p class="text-sm font-medium text-indigo-900">
                        Selected: {{ fullPersonalityType }}
                    </p>
                </div>
            </div>

            <!-- Optional Trait Percentages -->
            <div>
                <ButtonText
                    id="toggle-trait-percentages"
                    type="button"
                    @click="showTraitPercentages = !showTraitPercentages"
                >
                    {{ showTraitPercentages ? '− Hide' : '+ Add' }}
                    Trait Percentages (Optional)
                </ButtonText>

                <div v-if="showTraitPercentages" class="mt-4 space-y-3">
                    <p class="text-sm text-indigo-600">
                        Enter your trait percentages from
                        <LinkText
                            href="https://16personalities.com"
                            target="_blank"
                            rel="noopener noreferrer"
                            >16personalities.com</LinkText
                        >.
                    </p>

                    <div class="max-w-xs space-y-4">
                        <FormInput
                            id="mind"
                            v-model="form.traitPercentages.mind"
                            type="number"
                            label="Introversion/Extraversion"
                            :min="50"
                            :max="100"
                            class="text-right"
                        />

                        <FormInput
                            id="energy"
                            v-model="form.traitPercentages.energy"
                            type="number"
                            label="Intuitive/Observant"
                            :min="50"
                            :max="100"
                            class="text-right"
                        />

                        <FormInput
                            id="nature"
                            v-model="form.traitPercentages.nature"
                            type="number"
                            label="Thinking/Feeling"
                            :min="50"
                            :max="100"
                            class="text-right"
                        />

                        <FormInput
                            id="tactics"
                            v-model="form.traitPercentages.tactics"
                            type="number"
                            label="Judging/Prospecting"
                            :min="50"
                            :max="100"
                            class="text-right"
                        />

                        <div class="col-span-2">
                            <FormInput
                                id="identity-percent"
                                v-model="form.traitPercentages.identity"
                                type="number"
                                label="Assertive/Turbulent"
                                :min="50"
                                :max="100"
                                class="text-right"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <ButtonPrimary
                    type="submit"
                    :disabled="form.processing"
                    :loading="form.processing"
                >
                    Save
                </ButtonPrimary>

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
    </section>
</template>
