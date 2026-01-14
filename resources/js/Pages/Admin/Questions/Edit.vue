<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormCheckbox from '@/Components/Base/Form/FormCheckbox.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import FormTextarea from '@/Components/Base/Form/FormTextarea.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useNotification } from '@/Composables/ui/useNotification';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Variant {
    id: number;
    questionId: string;
    personalityPattern: string;
    phrasing: string;
}

interface Question {
    id: string;
    questionText: string;
    purpose: string;
    cognitiveRequirements: string[];
    priority: string;
    category: string;
    framework: string | null;
    isUniversal: boolean;
    isConditional: boolean;
    conditionText: string | null;
    displayOrder: number;
    variants: Variant[];
}

interface Props {
    question: Question;
    categories: string[];
    frameworks: string[];
    personalityPatterns: string[];
}

const props = defineProps<Props>();
const { countryRoute } = useCountryRoute();
const { success, error } = useNotification();

const cognitiveReqOptions = [
    'STRUCTURE',
    'DETAIL',
    'DECISIVE',
    'OBJECTIVE',
    'EMPATHY',
    'PERSUASION',
    'SYNTHESIS',
    'EXPLORE',
    'ITERATIVE',
    'AGENTIC',
    'RISK',
    'CREATIVE',
    'WARM',
    'PEDAGOGY',
    'ABSTRACTION',
    'VISION',
    'PARALLEL',
];

const form = useForm({
    questionText: props.question.questionText,
    purpose: props.question.purpose,
    cognitiveRequirements: props.question.cognitiveRequirements,
    priority: props.question.priority,
    category: props.question.category,
    framework: props.question.framework || '',
    isUniversal: props.question.isUniversal,
    isConditional: props.question.isConditional,
    conditionText: props.question.conditionText || '',
    displayOrder: props.question.displayOrder,
});

const selectedCogReqs = ref<string[]>(props.question.cognitiveRequirements);

const toggleCogReq = (req: string) => {
    const index = selectedCogReqs.value.indexOf(req);
    if (index > -1) {
        selectedCogReqs.value.splice(index, 1);
    } else {
        selectedCogReqs.value.push(req);
    }
};

watch(selectedCogReqs, (newValue) => {
    form.cognitiveRequirements = newValue;
});

// Variant management
const showAddVariantForm = ref(false);
const newVariantForm = useForm({
    personalityPattern: '',
    phrasing: '',
});

const variants = ref<Variant[]>(props.question.variants);

const addVariant = async () => {
    newVariantForm.post(
        countryRoute('admin.questions.variants.store', {
            question: props.question.id,
        }),
        {
            onSuccess: () => {
                // Refetch variants from the updated question
                success('Variant added successfully');
                newVariantForm.reset();
                showAddVariantForm.value = false;
            },
            onError: () => {
                error('Failed to add variant');
            },
        },
    );
};

const deleteVariant = (variantId: number) => {
    if (confirm('Are you sure you want to delete this variant?')) {
        useForm({}).delete(
            countryRoute('admin.questions.variants.destroy', {
                question: props.question.id,
                variant: variantId,
            }),
            {
                onSuccess: () => {
                    variants.value = variants.value.filter(
                        (v) => v.id !== variantId,
                    );
                    success('Variant deleted successfully');
                },
                onError: () => {
                    error('Failed to delete variant');
                },
            },
        );
    }
};

const submitUpdate = () => {
    form.put(
        countryRoute('admin.questions.update', { question: props.question.id }),
        {
            onSuccess: () => {
                success('Question updated successfully');
            },
        },
    );
};
</script>

<template>
    <Head :title="`Edit Question ${question.id}`" />

    <AppLayout>
        <HeaderPage :title="`Edit Question: ${question.id}`" />

        <ContainerPage>
            <!-- Question Edit Form -->
            <Card class="mb-6 max-w-4xl">
                <h2
                    class="mb-6 border-b border-indigo-200 pb-4 text-lg font-semibold text-indigo-900"
                >
                    Question Details
                </h2>

                <form class="space-y-6" @submit.prevent="submitUpdate">
                    <!-- Question ID (read-only) -->
                    <div>
                        <label class="block font-medium text-indigo-900"
                            >Question ID</label
                        >
                        <div
                            class="mt-1 rounded-md bg-indigo-50 px-4 py-2 font-mono text-indigo-900"
                        >
                            {{ question.id }}
                        </div>
                    </div>

                    <!-- Question Text -->
                    <FormTextarea
                        id="question-text"
                        v-model="form.questionText"
                        label="Question Text"
                        placeholder="Enter the question..."
                        data-testid="question-text"
                        required
                        :error="form.errors.questionText"
                    />

                    <!-- Purpose -->
                    <FormTextarea
                        id="purpose"
                        v-model="form.purpose"
                        label="Purpose"
                        placeholder="Why is this question asked?"
                        data-testid="purpose"
                        required
                        :error="form.errors.purpose"
                    />

                    <!-- Cognitive Requirements -->
                    <div>
                        <label class="block font-medium text-indigo-900">
                            Cognitive Requirements
                        </label>
                        <div
                            class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                        >
                            <FormCheckbox
                                v-for="req in cognitiveReqOptions"
                                :id="`cog-req-${req}`"
                                :key="req"
                                :label="req"
                                :checked="selectedCogReqs.includes(req)"
                                data-testid="cognitive-requirement"
                                @update:checked="toggleCogReq(req)"
                            />
                        </div>
                    </div>

                    <!-- Priority -->
                    <FormSelect
                        id="priority"
                        v-model="form.priority"
                        label="Priority"
                        data-testid="priority"
                        required
                        :error="form.errors.priority"
                    >
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </FormSelect>

                    <!-- Category -->
                    <FormSelect
                        id="category"
                        v-model="form.category"
                        label="Category"
                        data-testid="category"
                        required
                        :error="form.errors.category"
                    >
                        <option value="">Select a category...</option>
                        <option
                            v-for="cat in categories"
                            :key="cat"
                            :value="cat"
                        >
                            {{ cat }}
                        </option>
                    </FormSelect>

                    <!-- Framework -->
                    <FormSelect
                        id="framework"
                        v-model="form.framework"
                        label="Framework"
                        data-testid="framework"
                        :error="form.errors.framework"
                    >
                        <option value="">None (optional)</option>
                        <option v-for="fw in frameworks" :key="fw" :value="fw">
                            {{ fw }}
                        </option>
                    </FormSelect>

                    <!-- Is Universal -->
                    <FormCheckbox
                        id="is-universal"
                        v-model="form.isUniversal"
                        label="Universal Question"
                        help-text="Check if this question applies across most task categories"
                        data-testid="is-universal"
                        :error="form.errors.isUniversal"
                    />

                    <!-- Is Conditional -->
                    <FormCheckbox
                        id="is-conditional"
                        v-model="form.isConditional"
                        label="Conditional Question"
                        help-text="Check if this question only applies under certain conditions"
                        data-testid="is-conditional"
                        :error="form.errors.isConditional"
                    />

                    <!-- Condition Text (show if conditional) -->
                    <FormInput
                        v-if="form.isConditional"
                        id="condition-text"
                        v-model="form.conditionText"
                        label="Condition"
                        placeholder="e.g., research task, technical problem"
                        help-text="When should this question be used?"
                        data-testid="condition-text"
                        :error="form.errors.conditionText"
                    />

                    <!-- Display Order -->
                    <FormInput
                        id="display-order"
                        v-model.number="form.displayOrder"
                        type="number"
                        label="Display Order"
                        help-text="Order within category (0-based)"
                        data-testid="display-order"
                        :error="form.errors.displayOrder"
                    />

                    <!-- Form Actions -->
                    <div class="flex gap-4 border-t border-indigo-200 pt-6">
                        <ButtonPrimary
                            type="submit"
                            :disabled="form.processing"
                        >
                            <DynamicIcon
                                v-if="form.processing"
                                name="loader"
                                class="mr-2 h-4 w-4 animate-spin"
                            />
                            {{
                                form.processing
                                    ? 'Updating...'
                                    : 'Update Question'
                            }}
                        </ButtonPrimary>
                        <Link :href="countryRoute('admin.questions.index')">
                            <ButtonSecondary>Cancel</ButtonSecondary>
                        </Link>
                    </div>
                </form>
            </Card>

            <!-- Variants Section -->
            <Card class="max-w-4xl">
                <h2
                    class="mb-6 border-b border-indigo-200 pb-4 text-lg font-semibold text-indigo-900"
                >
                    Personality Variants ({{ variants.length }})
                </h2>

                <!-- Add Variant Form -->
                <div v-if="!showAddVariantForm" class="mb-6">
                    <ButtonPrimary @click="showAddVariantForm = true">
                        <DynamicIcon name="plus" class="mr-2 h-4 w-4" />
                        Add Variant
                    </ButtonPrimary>
                </div>

                <div
                    v-else
                    class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-4"
                >
                    <h3 class="mb-4 font-semibold text-indigo-900">
                        New Variant
                    </h3>
                    <form class="space-y-4" @submit.prevent="addVariant">
                        <FormSelect
                            id="new-personality-pattern"
                            v-model="newVariantForm.personalityPattern"
                            label="Personality Pattern"
                            data-testid="new-personality-pattern"
                            required
                            :error="newVariantForm.errors.personalityPattern"
                        >
                            <option value="">Select a pattern...</option>
                            <option
                                v-for="pattern in personalityPatterns"
                                :key="pattern"
                                :value="pattern"
                            >
                                {{ pattern }}
                            </option>
                        </FormSelect>

                        <FormTextarea
                            id="new-phrasing"
                            v-model="newVariantForm.phrasing"
                            label="Phrasing"
                            placeholder="How should this question be phrased for this personality pattern?"
                            data-testid="new-phrasing"
                            required
                            :error="newVariantForm.errors.phrasing"
                        />

                        <div class="flex gap-2 border-t border-indigo-200 pt-4">
                            <ButtonPrimary
                                type="submit"
                                :disabled="newVariantForm.processing"
                            >
                                {{
                                    newVariantForm.processing
                                        ? 'Adding...'
                                        : 'Add Variant'
                                }}
                            </ButtonPrimary>
                            <ButtonSecondary
                                @click="showAddVariantForm = false"
                            >
                                Cancel
                            </ButtonSecondary>
                        </div>
                    </form>
                </div>

                <!-- Variants List -->
                <div class="space-y-3">
                    <div
                        v-for="variant in variants"
                        :key="variant.id"
                        class="rounded-lg border border-indigo-200 bg-indigo-50 p-4"
                        :data-testid="`variant-${variant.id}`"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p
                                    class="font-mono text-sm font-semibold text-indigo-700"
                                >
                                    {{ variant.personalityPattern }}
                                </p>
                                <p class="mt-2 text-sm text-indigo-800">
                                    {{ variant.phrasing }}
                                </p>
                            </div>
                            <button
                                type="button"
                                data-testid="delete-variant-button"
                                class="ml-4 rounded-md bg-red-100 px-3 py-1 text-xs font-medium whitespace-nowrap text-red-700 transition-colors hover:bg-red-200"
                                @click="deleteVariant(variant.id)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="variants.length === 0"
                        class="rounded-lg bg-indigo-100 p-4 text-center"
                    >
                        <p class="text-sm text-indigo-700">
                            No personality variants yet. Add one to provide
                            adjusted phrasings.
                        </p>
                    </div>
                </div>
            </Card>
        </ContainerPage>
    </AppLayout>
</template>
