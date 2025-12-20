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

interface User {
    id: number;
    name: string;
    email: string;
    personalityType: string | null;
    isAdmin: boolean;
    createdAt: string;
    visitorsCount: number;
    promptRunsCount: number;
}

interface Props {
    users: {
        data: User[];
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
        route('admin.users.index'),
        { search: search.value },
        { preserveState: true, replace: true },
    );
}, 300);

watch(search, debouncedSearch);
</script>

<template>
    <Head title="Admin - Users" />
    <AppLayout>
        <HeaderPage title="Users">
            <template #actions>
                <Link
                    :href="route('admin.dashboard')"
                    class="text-sm text-indigo-600 hover:text-indigo-900"
                >
                    ← Back to Dashboard
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
                        placeholder="Search users..."
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
                                Name
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-indigo-500 uppercase"
                            >
                                Email
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-indigo-500 uppercase"
                            >
                                Visitors
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-indigo-500 uppercase"
                            >
                                Prompt Runs
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-indigo-200 bg-white">
                        <Link
                            v-for="user in props.users.data"
                            :key="user.id"
                            :href="route('admin.users.show', user.id)"
                            as="tr"
                            class="cursor-pointer transition hover:bg-indigo-50"
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
                                            Administrator
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-indigo-500">
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
