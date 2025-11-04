<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    personalityTypes: Record<string, string>;
}

const props = defineProps<Props>();

const form = useForm({
    personality_type: 'INTP-A',
    trait_percentages: {
        mind: null as number | null,
        energy: null as number | null,
        nature: null as number | null,
        tactics: null as number | null,
        identity: null as number | null,
    },
    task_description: 'I want to build a work-from-home office shed.',
});

const showTraitPercentages = ref(false);

const personalityTypeOptions = computed(() => {
    return Object.entries(props.personalityTypes).map(([value, label]) => ({
        value,
        label,
    }));
});

const submit = () => {
    // Only include trait_percentages if any are filled in
    const hasTraits = Object.values(form.trait_percentages).some(
        (val) => val !== null,
    );

    const submitData = {
        personality_type: form.personality_type,
        task_description: form.task_description,
        ...(hasTraits ? { trait_percentages: form.trait_percentages } : {}),
    };

    form.post(route('prompt-optimizer.store'));
};
</script>

<template>
    <Head title="Prompt Optimiser" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Prompt Optimiser
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="mb-6 text-gray-600">
                            Create optimised AI prompts customised to your
                            personality type and specific task requirements.
                        </p>

                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Personality Type Selection -->
                            <div>
                                <label
                                    for="personality_type"
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    Personality Type
                                    <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="personality_type"
                                    v-model="form.personality_type"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">
                                        Select your personality type
                                    </option>
                                    <option
                                        v-for="option in personalityTypeOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.personality_type"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.personality_type }}
                                </p>
                            </div>

                            <!-- Optional Trait Percentages -->
                            <div>
                                <button
                                    type="button"
                                    @click="
                                        showTraitPercentages =
                                            !showTraitPercentages
                                    "
                                    class="text-sm text-indigo-600 hover:text-indigo-800"
                                >
                                    {{
                                        showTraitPercentages
                                            ? '− Hide'
                                            : '+ Add'
                                    }}
                                    Trait Percentages (Optional)
                                </button>

                                <div
                                    v-if="showTraitPercentages"
                                    class="mt-4 space-y-3"
                                >
                                    <p class="text-sm text-gray-600">
                                        Enter your trait percentages from
                                        16personalities.com (optional)
                                    </p>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label
                                                for="mind"
                                                class="block text-sm text-gray-700"
                                            >
                                                Mind (Introversion/Extraversion)
                                            </label>
                                            <input
                                                type="number"
                                                id="mind"
                                                v-model.number="
                                                    form.trait_percentages.mind
                                                "
                                                min="0"
                                                max="100"
                                                placeholder="%"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </div>

                                        <div>
                                            <label
                                                for="energy"
                                                class="block text-sm text-gray-700"
                                            >
                                                Energy (Intuitive/Observant)
                                            </label>
                                            <input
                                                type="number"
                                                id="energy"
                                                v-model.number="
                                                    form.trait_percentages
                                                        .energy
                                                "
                                                min="0"
                                                max="100"
                                                placeholder="%"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </div>

                                        <div>
                                            <label
                                                for="nature"
                                                class="block text-sm text-gray-700"
                                            >
                                                Nature (Thinking/Feeling)
                                            </label>
                                            <input
                                                type="number"
                                                id="nature"
                                                v-model.number="
                                                    form.trait_percentages
                                                        .nature
                                                "
                                                min="0"
                                                max="100"
                                                placeholder="%"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </div>

                                        <div>
                                            <label
                                                for="tactics"
                                                class="block text-sm text-gray-700"
                                            >
                                                Tactics (Judging/Prospecting)
                                            </label>
                                            <input
                                                type="number"
                                                id="tactics"
                                                v-model.number="
                                                    form.trait_percentages
                                                        .tactics
                                                "
                                                min="0"
                                                max="100"
                                                placeholder="%"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </div>

                                        <div class="col-span-2">
                                            <label
                                                for="identity"
                                                class="block text-sm text-gray-700"
                                            >
                                                Identity (Assertive/Turbulent)
                                            </label>
                                            <input
                                                type="number"
                                                id="identity"
                                                v-model.number="
                                                    form.trait_percentages
                                                        .identity
                                                "
                                                min="0"
                                                max="100"
                                                placeholder="%"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Task Description -->
                            <div>
                                <label
                                    for="task_description"
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    Task Description
                                    <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    id="task_description"
                                    v-model="form.task_description"
                                    required
                                    rows="6"
                                    placeholder="Describe what you're trying to accomplish with AI..."
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                                <p
                                    v-if="form.errors.task_description"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.task_description }}
                                </p>
                                <p class="mt-1 text-sm text-gray-500">
                                    Minimum 10 characters. Be specific about
                                    your goals and requirements.
                                </p>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center justify-between">
                                <a
                                    :href="route('prompt-optimizer.history')"
                                    class="text-sm text-indigo-600 hover:text-indigo-800"
                                >
                                    View History
                                </a>

                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="justify-centre inline-flex rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                                >
                                    <span v-if="form.processing"
                                        >Processing...</span
                                    >
                                    <span v-else>Optimise Prompt</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
