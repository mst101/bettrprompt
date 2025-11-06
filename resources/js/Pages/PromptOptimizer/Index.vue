<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Redirect to profile if user hasn't set personality type
onMounted(() => {
    if (!user.value?.personalityType) {
        router.visit(route('profile.edit'), {
            data: {
                message: 'Please enter your personality type before submitting a task description.',
            },
        });
    }
});

const form = useForm({
    taskDescription: '',
});

const submit = () => {
    form.post(route('prompt-optimizer.store'));
};
</script>

<template>
    <Head title="Prompt Optimiser" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Prompt Optimiser
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="mb-6 text-gray-600">
                            Create optimised AI prompts customised to your
                            personality type and specific task requirements.
                        </p>

                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Task Description -->
                            <div>
                                <label
                                    for="taskDescription"
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    Task Description
                                    <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    id="taskDescription"
                                    v-model="form.taskDescription"
                                    required
                                    rows="6"
                                    placeholder="Describe what you're trying to accomplish..."
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                                <p
                                    v-if="form.errors.taskDescription"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.taskDescription }}
                                </p>
                                <p class="mt-1 text-sm text-gray-500">
                                    Minimum 10 characters. Be specific about
                                    your goals and requirements.
                                </p>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center justify-end">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="justify-centre inline-flex rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                                >
                                    <span v-if="form.processing"
                                        >Processing...</span
                                    >
                                    <span v-else>Optimise Prompt</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
