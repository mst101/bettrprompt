<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useNotification } from '@/Composables/ui/useNotification';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Question {
    id: string;
    questionText: string;
    purpose: string;
    category: string;
    framework: string | null;
    priority: string;
    isUniversal: boolean;
    variantsCount: number;
}

interface Props {
    questions: {
        data: Question[];
        links: {
            next: string | null;
            prev: string | null;
        };
        meta: {
            current_page: number;
            last_page: number;
            total: number;
        };
    };
    categories: string[];
    frameworks: string[];
    filters: {
        category: string;
        framework: string;
        search: string;
    };
}

const props = defineProps<Props>();

defineOptions({
    layout: AdminLayout,
});

const page = usePage();
const { countryRoute } = useCountryRoute();
const { success, error } = useNotification();

const selectedCategory = ref(props.filters.category);
const selectedFramework = ref(props.filters.framework);
const searchQuery = ref(props.filters.search);
const isRegenerating = ref(false);

const filteredQuestionCount = computed(() => props.questions.meta.total);

const applyFilters = () => {
    router.get(
        countryRoute('admin.questions.index'),
        {
            category: selectedCategory.value,
            framework: selectedFramework.value,
            search: searchQuery.value,
        },
        { preserveScroll: true },
    );
};

const clearFilters = () => {
    selectedCategory.value = '';
    selectedFramework.value = '';
    searchQuery.value = '';
    router.get(
        countryRoute('admin.questions.index'),
        {},
        { preserveScroll: true },
    );
};

const deleteQuestion = (questionId: string) => {
    if (confirm(`Are you sure you want to archive question ${questionId}?`)) {
        router.delete(
            countryRoute('admin.questions.destroy', { question: questionId }),
        );
    }
};

const regenerateMarkdown = async () => {
    isRegenerating.value = true;
    try {
        const response = await fetch(
            countryRoute('admin.questions.regenerate-markdown'),
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': page.props.csrf_token as string,
                    Accept: 'application/json',
                },
            },
        );

        if (response.ok) {
            success('Question bank markdown regenerated successfully');
        } else {
            error('Failed to regenerate markdown');
        }
    } catch {
        error('An error occurred while regenerating markdown');
    } finally {
        isRegenerating.value = false;
    }
};
</script>

<template>
    <Head title="Question Bank Management" />

    <HeaderPage
        title="Question Bank Management"
        subtitle="Edit and manage clarifying questions, then regenerate the question bank markdown"
    />

    <ContainerPage>
        <!-- Regenerate Button -->
        <Card class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-indigo-900">
                        Regenerate Markdown
                    </h3>
                    <p class="mt-1 text-sm text-indigo-700">
                        Click the button below to regenerate the
                        question_bank.md file from the current database state.
                    </p>
                </div>
                <ButtonPrimary
                    :disabled="isRegenerating"
                    class="whitespace-nowrap"
                    @click="regenerateMarkdown"
                >
                    <DynamicIcon
                        v-if="isRegenerating"
                        name="loader"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    {{
                        isRegenerating
                            ? 'Regenerating...'
                            : 'Regenerate Markdown'
                    }}
                </ButtonPrimary>
            </div>
        </Card>

        <!-- Filters -->
        <Card class="mb-6">
            <div class="space-y-4">
                <h3 class="font-semibold text-indigo-900">Filters</h3>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <FormInput
                        id="search"
                        v-model="searchQuery"
                        placeholder="Search question text..."
                        data-testid="search-input"
                    />

                    <FormSelect
                        id="category-filter"
                        v-model="selectedCategory"
                        label="Category"
                        data-testid="category-filter"
                    >
                        <option value="">All Categories</option>
                        <option
                            v-for="cat in categories"
                            :key="cat"
                            :value="cat"
                        >
                            {{ cat }}
                        </option>
                    </FormSelect>

                    <FormSelect
                        id="framework-filter"
                        v-model="selectedFramework"
                        label="Framework"
                        data-testid="framework-filter"
                    >
                        <option value="">All Frameworks</option>
                        <option v-for="fw in frameworks" :key="fw" :value="fw">
                            {{ fw }}
                        </option>
                    </FormSelect>

                    <div class="flex items-end gap-2">
                        <ButtonPrimary class="flex-1" @click="applyFilters">
                            Apply Filters
                        </ButtonPrimary>
                        <ButtonSecondary class="flex-1" @click="clearFilters">
                            Clear
                        </ButtonSecondary>
                    </div>
                </div>
            </div>
        </Card>

        <!-- Questions List -->
        <Card>
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-indigo-900">
                        Questions ({{ filteredQuestionCount }})
                    </h3>
                </div>
                <Link :href="countryRoute('admin.questions.create')">
                    <ButtonPrimary>
                        <DynamicIcon name="plus" class="mr-2 h-4 w-4" />
                        New Question
                    </ButtonPrimary>
                </Link>
            </div>

            <!-- Questions Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-indigo-200 bg-indigo-50">
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold text-indigo-900"
                                data-testid="table-header-id"
                            >
                                ID
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold text-indigo-900"
                                data-testid="table-header-question"
                            >
                                Question
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold text-indigo-900"
                                data-testid="table-header-category"
                            >
                                Category
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold text-indigo-900"
                                data-testid="table-header-framework"
                            >
                                Framework
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold text-indigo-900"
                                data-testid="table-header-priority"
                            >
                                Priority
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold text-indigo-900"
                                data-testid="table-header-variants"
                            >
                                Variants
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold text-indigo-900"
                                data-testid="table-header-actions"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="question in props.questions.data"
                            :key="question.id"
                            class="border-b border-indigo-100 hover:bg-indigo-50"
                            :data-testid="`question-row-${question.id}`"
                        >
                            <td
                                class="px-4 py-3 font-mono text-sm text-indigo-900"
                            >
                                {{ question.id }}
                            </td>
                            <td
                                class="max-w-xs truncate px-4 py-3 text-sm text-indigo-800"
                            >
                                {{ question.questionText }}
                            </td>
                            <td class="px-4 py-3 text-sm text-indigo-700">
                                <span
                                    class="inline-block rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800"
                                >
                                    {{ question.category }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-indigo-700">
                                <span
                                    v-if="question.framework"
                                    class="inline-block rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800"
                                >
                                    {{ question.framework }}
                                </span>
                                <span v-else class="text-indigo-400">—</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-indigo-700">
                                <span
                                    :class="{
                                        'font-medium text-red-600':
                                            question.priority === 'high',
                                        'font-medium text-yellow-600':
                                            question.priority === 'medium',
                                        'text-gray-600':
                                            question.priority === 'low',
                                    }"
                                >
                                    {{ question.priority }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-indigo-700">
                                <span
                                    v-if="question.variantsCount > 0"
                                    class="inline-block rounded-full bg-purple-100 px-3 py-1 text-xs font-medium text-purple-800"
                                >
                                    {{ question.variantsCount }}
                                </span>
                                <span v-else class="text-indigo-400">0</span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex gap-2">
                                    <Link
                                        :href="
                                            countryRoute(
                                                'admin.questions.edit',
                                                { question: question.id },
                                            )
                                        "
                                    >
                                        <ButtonSecondary class="text-xs">
                                            Edit
                                        </ButtonSecondary>
                                    </Link>
                                    <button
                                        type="button"
                                        data-testid="delete-button"
                                        class="rounded-md bg-red-100 px-3 py-1 text-xs font-medium text-red-700 transition-colors hover:bg-red-200"
                                        @click="deleteQuestion(question.id)"
                                    >
                                        Archive
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="props.questions.data.length === 0">
                            <td
                                colspan="7"
                                class="px-4 py-8 text-center text-indigo-600"
                            >
                                No questions found. Try adjusting your filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="props.questions.meta.last_page > 1"
                class="mt-6 flex items-center justify-between border-t border-indigo-200 pt-4"
            >
                <span class="text-sm text-indigo-700">
                    Page {{ props.questions.meta.current_page }} of
                    {{ props.questions.meta.last_page }}
                </span>
                <div class="flex gap-2">
                    <Link
                        v-if="props.questions.links.prev"
                        :href="props.questions.links.prev"
                        :preserve-scroll="true"
                    >
                        <ButtonSecondary>Previous</ButtonSecondary>
                    </Link>
                    <Link
                        v-if="props.questions.links.next"
                        :href="props.questions.links.next"
                        :preserve-scroll="true"
                    >
                        <ButtonSecondary>Next</ButtonSecondary>
                    </Link>
                </div>
            </div>
        </Card>
    </ContainerPage>
</template>
