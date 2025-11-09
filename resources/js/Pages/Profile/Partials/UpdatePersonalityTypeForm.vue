<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TraitPercentageInput from '@/Components/TraitPercentageInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Props {
    personalityTypes: Record<string, string>;
}

const props = defineProps<Props>();

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Load from user data or fallback to local storage
const savedPersonalityType = user.value?.personalityType;
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
    traitPercentages: (user.value?.traitPercentages || {
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
    form.patch(route('profile.personality.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Keep the CTA visible after saving; do not rely on recentlySuccessful (which fades)
            showTaskCta.value = true;
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Your Personality Type
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your personality type to get more personalised AI
                prompts.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <!-- Personality Type Selection -->
            <div class="space-y-4">
                <div>
                    <InputLabel
                        for="personalityBase"
                        value="Personality Type"
                    />
                    <select
                        id="personalityBase"
                        v-model="personalityBase"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500"
                        autofocus
                    >
                        <option value="">Select your personality type</option>
                        <option
                            v-for="option in personalityTypeOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <InputError
                        :message="form.errors.personalityType"
                        class="mt-2"
                    />
                </div>

                <!-- Identity Selection -->
                <div v-if="personalityBase">
                    <InputLabel for="identity" value="Identity" />
                    <div class="mt-2 flex gap-6">
                        <label class="flex items-center">
                            <input
                                v-model="identity"
                                type="radio"
                                value="A"
                                class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="ml-2 text-sm text-gray-700"
                                >Assertive (A)</span
                            >
                        </label>
                        <label class="flex items-center">
                            <input
                                v-model="identity"
                                type="radio"
                                value="T"
                                class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="ml-2 text-sm text-gray-700"
                                >Turbulent (T)</span
                            >
                        </label>
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
                <button
                    type="button"
                    @click="showTraitPercentages = !showTraitPercentages"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    {{ showTraitPercentages ? '− Hide' : '+ Add' }}
                    Trait Percentages (Optional)
                </button>

                <div v-if="showTraitPercentages" class="mt-4 space-y-3">
                    <p class="text-sm text-gray-600">
                        Enter your trait percentages from 16personalities.com
                        (optional)
                    </p>

                    <div class="grid grid-cols-2 gap-4">
                        <TraitPercentageInput
                            id="mind"
                            label="Mind (Introversion/Extraversion)"
                            v-model="form.traitPercentages.mind"
                        />

                        <TraitPercentageInput
                            id="energy"
                            label="Energy (Intuitive/Observant)"
                            v-model="form.traitPercentages.energy"
                        />

                        <TraitPercentageInput
                            id="nature"
                            label="Nature (Thinking/Feeling)"
                            v-model="form.traitPercentages.nature"
                        />

                        <TraitPercentageInput
                            id="tactics"
                            label="Tactics (Judging/Prospecting)"
                            v-model="form.traitPercentages.tactics"
                        />

                        <div class="col-span-2">
                            <TraitPercentageInput
                                id="identityPercent"
                                label="Identity (Assertive/Turbulent)"
                                v-model="form.traitPercentages.identity"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <!-- Persist CTA after a successful save in this session -->
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <Link
                        v-if="showTaskCta"
                        :href="route('prompt-optimizer.index')"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Enter your Task Description →
                    </Link>
                </Transition>
            </div>
        </form>
    </section>
</template>
