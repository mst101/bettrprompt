<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';

interface Task {
    task_id: number;
    task_description: string;
    runs_count: number;
}

interface Props {
    tasks: {
        data: Task[];
        links: Array<Record<string, unknown>>;
        currentPage: number;
        lastPage: number;
    };
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

const search = ref(props.filters.search || '');

const debouncedSearch = useDebounceFn(() => {
    router.get(
        countryRoute('admin.tasks.index'),
        { search: search.value },
        { preserveState: true, replace: true },
    );
}, 300);

watch(search, debouncedSearch);
</script>

<template>
    <Head :title="$t('admin.tasks.headTitle')" />

    <HeaderPage :title="$t('admin.tasks.title')">
        <template #actions>
            <Link
                :href="countryRoute('admin.dashboard')"
                class="text-sm text-indigo-600 hover:text-indigo-900"
            >
                {{ $t('admin.tasks.backToDashboard') }}
            </Link>
        </template>
    </HeaderPage>

    <ContainerPage>
        <!-- Search -->
        <Card class="mb-6">
            <div class="relative">
                <DynamicIcon
                    name="search"
                    class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-indigo-400"
                />
                <FormInput
                    id="search-tasks"
                    v-model="search"
                    class="pl-12"
                    label=""
                    type="text"
                    :placeholder="$t('admin.tasks.searchPlaceholder')"
                />
            </div>
        </Card>

        <!-- Tasks List -->
        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-indigo-200">
                    <thead class="bg-indigo-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-500 uppercase"
                            >
                                {{ $t('admin.tasks.columns.description') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-500 uppercase"
                            >
                                {{ $t('admin.tasks.columns.runs') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-indigo-200 bg-white dark:bg-indigo-50"
                    >
                        <Link
                            v-for="task in props.tasks.data"
                            :key="task.task_description"
                            :href="
                                countryRoute('admin.tasks.show', {
                                    taskId: task.task_id,
                                })
                            "
                            as="tr"
                            class="cursor-pointer transition hover:bg-indigo-50 dark:hover:bg-indigo-100"
                        >
                            <td class="px-6 py-4 text-sm text-indigo-900">
                                {{ task.task_description }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex rounded-full bg-blue-100 px-2 text-xs leading-5 font-semibold text-blue-800"
                                >
                                    {{ task.runs_count }}
                                </span>
                            </td>
                        </Link>
                        <tr
                            v-if="props.tasks.data.length === 0"
                            class="hover:bg-indigo-50"
                        >
                            <td
                                colspan="2"
                                class="px-6 py-4 text-center text-sm text-indigo-500"
                            >
                                {{ $t('admin.tasks.empty') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="props.tasks.lastPage > 1"
                class="mt-4 flex items-center justify-between border-t border-indigo-100 px-4 py-3 sm:px-6"
            >
                <div class="flex flex-1 justify-between sm:hidden">
                    <Link
                        v-if="props.tasks.currentPage > 1"
                        :href="props.tasks.links[0].url"
                        class="relative inline-flex items-center rounded-md border border-indigo-100 bg-white px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50"
                    >
                        {{ $t('admin.pagination.previous') }}
                    </Link>
                    <Link
                        v-if="props.tasks.currentPage < props.tasks.lastPage"
                        :href="
                            props.tasks.links[props.tasks.links.length - 1].url
                        "
                        class="relative ml-3 inline-flex items-center rounded-md border border-indigo-100 bg-white px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50"
                    >
                        {{ $t('admin.pagination.next') }}
                    </Link>
                </div>
                <div
                    class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between"
                >
                    <div>
                        <p class="text-sm text-indigo-700">
                            {{
                                $t('admin.pagination.pageOf', {
                                    current: props.tasks.currentPage,
                                    total: props.tasks.lastPage,
                                })
                            }}
                        </p>
                    </div>
                    <div>
                        <nav
                            class="isolate inline-flex -space-x-px rounded-md shadow-sm"
                        >
                            <Link
                                v-for="link in props.tasks.links"
                                v-show="link.url"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    link.active
                                        ? 'z-10 bg-indigo-600 text-white'
                                        : 'bg-white text-indigo-700 hover:bg-indigo-50',
                                    'relative inline-flex items-center border border-indigo-100 px-4 py-2 text-sm font-medium',
                                ]"
                                :text="link.label"
                            />
                        </nav>
                    </div>
                </div>
            </div>
        </Card>
    </ContainerPage>
</template>
