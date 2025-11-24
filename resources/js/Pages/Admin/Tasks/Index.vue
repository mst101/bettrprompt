<script setup lang="ts">
import Card from '@/Components/Card.vue';
import ContainerPage from '@/Components/ContainerPage.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import FormInput from '@/Components/FormInput.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
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
        current_page: number;
        last_page: number;
    };
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');

const debouncedSearch = useDebounceFn(() => {
    router.get(
        route('admin.tasks.index'),
        { search: search.value },
        { preserveState: true, replace: true },
    );
}, 300);

watch(search, debouncedSearch);
</script>

<template>
    <Head title="Admin - Tasks" />

    <AppLayout>
        <HeaderPage title="Tasks">
            <template #actions>
                <Link
                    :href="route('admin.dashboard')"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    ← Back to Dashboard
                </Link>
            </template>
        </HeaderPage>

        <ContainerPage>
            <!-- Search -->
            <Card class="mb-6">
                <div class="relative">
                    <DynamicIcon
                        name="search"
                        class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-gray-400"
                    />
                    <FormInput
                        id="search-tasks"
                        v-model="search"
                        class="pl-12"
                        label=""
                        type="text"
                        placeholder="Search tasks..."
                    />
                </div>
            </Card>

            <!-- Tasks List -->
            <Card>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Task Description
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Runs
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <Link
                                v-for="task in props.tasks.data"
                                :key="task.task_description"
                                :href="
                                    route('admin.tasks.show', {
                                        taskId: task.task_id,
                                    })
                                "
                                as="tr"
                                class="cursor-pointer transition hover:bg-gray-50"
                            >
                                <td class="px-6 py-4 text-sm text-gray-900">
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
                                class="hover:bg-gray-50"
                            >
                                <td
                                    colspan="2"
                                    class="px-6 py-4 text-center text-sm text-gray-500"
                                >
                                    No tasks found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="props.tasks.last_page > 1"
                    class="mt-4 flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6"
                >
                    <div class="flex flex-1 justify-between sm:hidden">
                        <Link
                            v-if="props.tasks.current_page > 1"
                            :href="props.tasks.links[0].url"
                            class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Previous
                        </Link>
                        <Link
                            v-if="
                                props.tasks.current_page < props.tasks.last_page
                            "
                            :href="
                                props.tasks.links[props.tasks.links.length - 1]
                                    .url
                            "
                            class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Next
                        </Link>
                    </div>
                    <div
                        class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="text-sm text-gray-700">
                                Page
                                <span class="font-medium">{{
                                    props.tasks.current_page
                                }}</span>
                                of
                                <span class="font-medium">{{
                                    props.tasks.last_page
                                }}</span>
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
                                            : 'bg-white text-gray-700 hover:bg-gray-50',
                                        'relative inline-flex items-center border border-gray-300 px-4 py-2 text-sm font-medium',
                                    ]"
                                    :text="link.label"
                                />
                            </nav>
                        </div>
                    </div>
                </div>
            </Card>
        </ContainerPage>
    </AppLayout>
</template>
