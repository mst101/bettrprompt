<script setup lang="ts">
import Card from '@/Components/Card.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface Props {
    stats: {
        total_users: number;
        total_prompt_runs: number;
        unique_tasks: number;
        completed_runs: number;
    };
}

const props = defineProps<Props>();
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout>
        <HeaderPage title="Admin Dashboard" />

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Stats Grid -->
                <div class="mb-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Users -->
                    <Card>
                        <div class="flex items-center">
                            <div
                                class="rounded-lg bg-blue-100 p-3 text-blue-600"
                            >
                                <DynamicIcon name="users" class="h-6 w-6" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">Total Users</p>
                                <p class="text-2xl font-semibold text-gray-900">
                                    {{ props.stats.total_users }}
                                </p>
                            </div>
                        </div>
                    </Card>

                    <!-- Total Prompt Runs -->
                    <Card>
                        <div class="flex items-center">
                            <div
                                class="rounded-lg bg-green-100 p-3 text-green-600"
                            >
                                <DynamicIcon
                                    name="clipboard-list"
                                    class="h-6 w-6"
                                />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">Prompt Runs</p>
                                <p class="text-2xl font-semibold text-gray-900">
                                    {{ props.stats.total_prompt_runs }}
                                </p>
                            </div>
                        </div>
                    </Card>

                    <!-- Unique Tasks -->
                    <Card>
                        <div class="flex items-center">
                            <div
                                class="rounded-lg bg-purple-100 p-3 text-purple-600"
                            >
                                <DynamicIcon name="tag" class="h-6 w-6" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">
                                    Unique Tasks
                                </p>
                                <p class="text-2xl font-semibold text-gray-900">
                                    {{ props.stats.unique_tasks }}
                                </p>
                            </div>
                        </div>
                    </Card>

                    <!-- Completed Runs -->
                    <Card>
                        <div class="flex items-center">
                            <div
                                class="rounded-lg bg-indigo-100 p-3 text-indigo-600"
                            >
                                <DynamicIcon
                                    name="check-circle"
                                    class="h-6 w-6"
                                />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">
                                    Completed Runs
                                </p>
                                <p class="text-2xl font-semibold text-gray-900">
                                    {{ props.stats.completed_runs }}
                                </p>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Quick Links -->
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Users Link -->
                    <Link :href="route('admin.users.index')">
                        <Card
                            class="transition-shadow duration-200 hover:shadow-lg"
                        >
                            <div class="flex items-center">
                                <DynamicIcon
                                    name="users"
                                    class="h-8 w-8 text-blue-600"
                                />
                                <div class="ml-4">
                                    <h3 class="font-semibold text-gray-900">
                                        Manage Users
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        View and search users
                                    </p>
                                </div>
                                <DynamicIcon
                                    name="chevron-right"
                                    class="ml-auto h-5 w-5 text-gray-400"
                                />
                            </div>
                        </Card>
                    </Link>

                    <!-- Tasks Link -->
                    <Link :href="route('admin.tasks.index')">
                        <Card
                            class="transition-shadow duration-200 hover:shadow-lg"
                        >
                            <div class="flex items-center">
                                <DynamicIcon
                                    name="clipboard-list"
                                    class="h-8 w-8 text-green-600"
                                />
                                <div class="ml-4">
                                    <h3 class="font-semibold text-gray-900">
                                        Browse Tasks
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        Filter by task description
                                    </p>
                                </div>
                                <DynamicIcon
                                    name="chevron-right"
                                    class="ml-auto h-5 w-5 text-gray-400"
                                />
                            </div>
                        </Card>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
