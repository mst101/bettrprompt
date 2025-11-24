<script setup lang="ts">
import Card from '@/Components/Card.vue';
import ContainerPage from '@/Components/ContainerPage.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface User {
    id: number;
    name: string;
    email: string;
    personality_type: string | null;
    is_admin: boolean;
    created_at: string;
}

interface Props {
    user: User;
    prompt_runs_count: number;
}

const props = defineProps<Props>();
</script>

<template>
    <Head :title="`Admin - User: ${props.user.name}`" />
    <AppLayout>
        <HeaderPage title="User Details">
            <template #actions>
                <Link
                    :href="route('admin.users.index')"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    ← Back to Users
                </Link>
            </template>
        </HeaderPage>

        <ContainerPage>
            <Card>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Name
                        </label>
                        <p class="mt-1 text-gray-900">
                            {{ props.user.name }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Email
                        </label>
                        <p class="mt-1 text-gray-900">
                            {{ props.user.email }}
                        </p>
                    </div>
                    <div v-if="props.user.personality_type">
                        <label class="block text-sm font-medium text-gray-700">
                            Personality Type
                        </label>
                        <p class="mt-1">
                            <span
                                class="inline-flex rounded-full bg-purple-100 px-3 py-1 text-sm font-semibold text-purple-800"
                            >
                                {{ props.user.personality_type }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Administrator
                        </label>
                        <p class="mt-1">
                            <span
                                :class="[
                                    'inline-flex rounded-full px-3 py-1 text-sm font-semibold',
                                    props.user.is_admin
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-gray-100 text-gray-800',
                                ]"
                            >
                                {{ props.user.is_admin ? 'Yes' : 'No' }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Total Prompt Runs
                        </label>
                        <p class="mt-1 text-gray-900">
                            {{ props.prompt_runs_count }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Joined
                        </label>
                        <p class="mt-1 text-gray-900">
                            {{
                                new Date(
                                    props.user.created_at,
                                ).toLocaleDateString()
                            }}
                        </p>
                    </div>
                </div>
            </Card>
        </ContainerPage>
    </AppLayout>
</template>
