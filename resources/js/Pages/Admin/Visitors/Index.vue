<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Paginated, VisitorListResource } from '@/Types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    visitors: Paginated<VisitorListResource>;
    search: string | null;
}

const props = defineProps<Props>();

defineOptions({
    layout: AdminLayout,
});

const { countryRoute } = useCountryRoute();

const searchQuery = ref(props.search || '');
let debounceTimeout: ReturnType<typeof setTimeout>;

const debouncedSearch = () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        router.get(
            window.location.pathname,
            { search: searchQuery.value },
            { preserveState: true, preserveScroll: true },
        );
    }, 300);
};

const formatDate = (dateStr: string): string => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const truncateId = (id: string): string => {
    return id.substring(0, 8) + '...';
};
</script>

<template>
    <Head title="Visitors - Admin" />

    <HeaderPage title="Visitors" />

    <ContainerPage>
        <!-- Search bar -->
        <div class="mb-6">
            <input
                id="visitor-search"
                v-model="searchQuery"
                type="text"
                placeholder="Search by visitor ID, country, or linked user..."
                class="w-full rounded-lg border border-indigo-200 px-4 py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                data-testid="visitor-search"
                @input="debouncedSearch"
            />
        </div>

        <!-- Visitors table -->
        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-indigo-200">
                    <thead class="bg-indigo-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                            >
                                Visitor ID
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                            >
                                Linked User
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                            >
                                Country
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                            >
                                Sessions
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                            >
                                First Seen
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-indigo-100 bg-white">
                        <tr
                            v-for="visitor in visitors.data"
                            :key="visitor.id"
                            class="hover:bg-indigo-50"
                        >
                            <td
                                class="px-6 py-4 font-mono text-sm whitespace-nowrap text-indigo-900"
                            >
                                {{ truncateId(visitor.id) }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                <Link
                                    v-if="visitor.user"
                                    :href="
                                        countryRoute('admin.users.show', {
                                            user: visitor.user.id,
                                        })
                                    "
                                    class="text-indigo-600 hover:text-indigo-900 hover:underline"
                                >
                                    {{ visitor.user.name }}
                                </Link>
                                <span v-else class="text-indigo-400">
                                    Anonymous
                                </span>
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                            >
                                {{ visitor.countryCode || 'N/A' }}
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                            >
                                {{ visitor.sessionsCount }}
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                            >
                                {{ formatDate(visitor.createdAt) }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                <Link
                                    :href="
                                        countryRoute('admin.visitors.show', {
                                            visitor: visitor.id,
                                        })
                                    "
                                    class="font-medium text-indigo-600 hover:text-indigo-900 hover:underline"
                                    data-testid="view-visitor"
                                >
                                    View
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="visitors.data.length === 0">
                            <td
                                colspan="6"
                                class="px-6 py-4 text-center text-sm text-indigo-500"
                            >
                                No visitors found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <!-- Pagination -->
        <div v-if="visitors.meta.lastPage > 1" class="mt-6 flex justify-center">
            <Pagination :meta="visitors.meta" />
        </div>
    </ContainerPage>
</template>
