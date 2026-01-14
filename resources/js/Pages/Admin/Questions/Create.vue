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
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Props {
    categories: string[];
    frameworks: string[];
}

defineProps<Props>();

defineOptions({
    layout: AdminLayout,
});

const { countryRoute } = useCountryRoute();
useNotification();

const form = useForm({
    id: '',
    questionText: '',
    purpose: '',
    priority: 'high',
    taskCategoryCode: '',
    frameworkCode: '',
    isUniversal: false,
    isConditional: false,
    conditionText: '',
    displayOrder: 0,
});

const submit = () => {
    form.post(countryRoute('admin.questions.store'));
};
</script>

<template>
    <Head title="Create Question" />

    <HeaderPage title="Create Question" />

    <ContainerPage>
        <Card class="max-w-4xl">
            <form class="space-y-6" @submit.prevent="submit">
                <!-- Question ID -->
                <FormInput
                    id="question-id"
                    v-model="form.id"
                    label="Question ID"
                    placeholder="e.g., U1, D1, COS3"
                    help-text="Unique identifier for the question"
                    data-testid="question-id"
                    required
                    :error="form.errors.id"
                />

                <!-- Question Text -->
                <FormTextarea
                    id="question-text"
                    v-model="form.questionText"
                    label="Question Text"
                    placeholder="Enter the question..."
                    help-text="The main question to ask"
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
                    help-text="Brief description of the question's purpose"
                    data-testid="purpose"
                    required
                    :error="form.errors.purpose"
                />

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
                    id="task-category-code"
                    v-model="form.taskCategoryCode"
                    label="Category"
                    data-testid="task-category-code"
                    required
                    :error="form.errors.taskCategoryCode"
                >
                    <option value="">Select a category...</option>
                    <option v-for="cat in categories" :key="cat" :value="cat">
                        {{ cat }}
                    </option>
                </FormSelect>

                <!-- Framework -->
                <FormSelect
                    id="framework-code"
                    v-model="form.frameworkCode"
                    label="Framework"
                    data-testid="framework-code"
                    :error="form.errors.frameworkCode"
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
                    <ButtonPrimary type="submit" :disabled="form.processing">
                        <DynamicIcon
                            v-if="form.processing"
                            name="loader"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        {{
                            form.processing ? 'Creating...' : 'Create Question'
                        }}
                    </ButtonPrimary>
                    <Link :href="countryRoute('admin.questions.index')">
                        <ButtonSecondary>Cancel</ButtonSecondary>
                    </Link>
                </div>
            </form>
        </Card>
    </ContainerPage>
</template>
