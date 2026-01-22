<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import TableHeaderSortable from '@/Components/Base/TableHeaderSortable.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { UserListResource } from '@/Types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

interface Props {
    users: {
        data: UserListResource[];
        links: Array<Record<string, unknown>>;
        currentPage: number;
        lastPage: number;
    };
    filters: {
        search?: string;
        sortBy: string;
        sortDirection: string;
    };
}

const props = defineProps<Props>();
const search = ref(props.filters.search || '');
const { countryRoute } = useCountryRoute();

const debouncedSearch = useDebounceFn(() => {
    router.get(
        countryRoute('admin.users.index'),
        {
            search: search.value,
            sort_by: props.filters.sortBy,
            sort_direction: props.filters.sortDirection,
        },
        { preserveState: true, replace: true },
    );
}, 300);

watch(search, debouncedSearch);

const sortBy = (column: string) => {
    const currentSortBy = props.filters.sortBy;
    const currentDirection = props.filters.sortDirection;

    let newDirection = 'asc';
    if (currentSortBy === column && currentDirection === 'asc') {
        newDirection = 'desc';
    }

    router.get(
        countryRoute('admin.users.index'),
        {
            search: search.value,
            sort_by: column,
            sort_direction: newDirection,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const sortDirection = computed(() => {
    return props.filters.sortDirection;
});
</script>

<template>
    <Head :title="$t('admin.users.headTitle')" />
    <AppLayout>
        <HeaderPage :title="$t('admin.users.title')">
            <template #actions>
                <Link
                    :href="countryRoute('admin.dashboard')"
                    class="text-sm text-indigo-600 hover:text-indigo-900"
                >
                    {{ $t('admin.users.backToDashboard') }}
                </Link>
            </template>
        </HeaderPage>

        <ContainerPage>
            <Card class="mb-6">
                <div class="relative">
                    <DynamicIcon
                        name="search"
                        class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-indigo-400"
                    />
                    <FormInput
                        id="task-search"
                        v-model="search"
                        label=""
                        type="text"
                        :placeholder="$t('admin.users.searchPlaceholder')"
                        class="w-full rounded-lg border-indigo-100 py-2 pr-4 pl-10"
                    />
                </div>
            </Card>

            <Card>
                <table class="min-w-full divide-y divide-indigo-200">
                    <thead class="bg-indigo-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-indigo-500 uppercase"
                            >
                                <TableHeaderSortable
                                    column="name"
                                    :current-sort="filters.sortBy"
                                    :sort-direction="sortDirection"
                                    @sort="sortBy"
                                >
                                    {{ $t('admin.users.columns.name') }}
                                </TableHeaderSortable>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-indigo-500 uppercase"
                            >
                                <TableHeaderSortable
                                    column="email"
                                    :current-sort="filters.sortBy"
                                    :sort-direction="sortDirection"
                                    @sort="sortBy"
                                >
                                    {{ $t('admin.users.columns.email') }}
                                </TableHeaderSortable>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-indigo-500 uppercase"
                            >
                                {{ $t('admin.users.columns.visitors') }}
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-indigo-500 uppercase"
                            >
                                {{ $t('admin.users.columns.promptRuns') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-indigo-200 bg-white dark:bg-indigo-50"
                    >
                        <Link
                            v-for="user in props.users.data"
                            :key="user.id"
                            :href="countryRoute('admin.users.show', { user })"
                            as="tr"
                            class="cursor-pointer transition hover:bg-indigo-50 dark:hover:bg-indigo-100"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div>
                                        <div
                                            class="font-medium text-indigo-900"
                                        >
                                            {{ user.name }}
                                        </div>
                                        <div
                                            v-if="user.isAdmin"
                                            class="text-xs text-indigo-600"
                                        >
                                            {{ $t('admin.users.adminLabel') }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-indigo-600">
                                {{ user.email }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold text-blue-800"
                                >
                                    {{ user.visitorsCount }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold text-green-800"
                                >
                                    {{ user.promptRunsCount }}
                                </span>
                            </td>
                        </Link>
                    </tbody>
                </table>
            </Card>
        </ContainerPage>
    </AppLayout>
</template>
