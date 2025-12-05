<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { useForm } from '@inertiajs/vue3';

interface Props {
    toolsData: {
        preferredTools: string[];
        primaryProgrammingLanguage: string | null;
    };
}

const props = defineProps<Props>();

const toolCategories = {
    'Operating Systems': ['Windows', 'macOS', 'Linux', 'Ubuntu', 'Fedora'],
    'IDEs & Editors': [
        'VS Code',
        'JetBrains IDEs',
        'Visual Studio',
        'Sublime Text',
        'Vim',
        'Neovim',
    ],
    'Cloud Platforms': [
        'AWS',
        'Google Cloud',
        'Microsoft Azure',
        'DigitalOcean',
        'Heroku',
    ],
    'AI & ML Tools': [
        'ChatGPT',
        'GitHub Copilot',
        'Claude',
        'TensorFlow',
        'PyTorch',
    ],
    'Design Tools': [
        'Figma',
        'Adobe XD',
        'Sketch',
        'Adobe Photoshop',
        'Inkscape',
    ],
    'Project Management': ['Jira', 'Asana', 'Monday.com', 'Notion', 'Trello'],
};

const programmingLanguages = [
    'JavaScript',
    'Python',
    'TypeScript',
    'Java',
    'C#',
    'Go',
    'Rust',
    'PHP',
    'Ruby',
    'C++',
    'Kotlin',
    'Swift',
    'Shell/Bash',
    'SQL',
    'R',
];

const form = useForm({
    preferred_tools: props.toolsData.preferredTools || [],
    primary_programming_language:
        props.toolsData.primaryProgrammingLanguage || '',
});

const toggleTool = (tool: string) => {
    const index = form.preferred_tools.indexOf(tool);
    if (index > -1) {
        form.preferred_tools.splice(index, 1);
    } else {
        form.preferred_tools.push(tool);
    }
};

const submit = () => {
    form.patch(route('profile.tools.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Tools & Technologies
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Share the tools and programming languages you work with to
                improve prompt recommendations.
            </p>
        </header>

        <form class="mt-6 space-y-6" @submit.prevent="submit">
            <!-- Preferred Tools -->
            <div>
                <div class="mb-4">
                    <InputLabel
                        for="tools"
                        value="Preferred Tools (Click to select)"
                        :required="false"
                    />
                    <p class="mt-1 text-sm text-gray-600">
                        You've selected
                        {{ form.preferred_tools.length }} tool(s)
                    </p>
                </div>

                <!-- Collapsible Tool Categories -->
                <div class="space-y-4">
                    <div
                        v-for="(tools, category) in toolCategories"
                        :key="category"
                        class="rounded-lg border border-gray-200"
                    >
                        <button
                            type="button"
                            class="flex w-full items-center justify-between rounded-lg px-4 py-3 font-medium text-gray-900 hover:bg-gray-50"
                            @click="
                                (e) => {
                                    const el =
                                        e.currentTarget.nextElementSibling;
                                    el?.classList.toggle('hidden');
                                }
                            "
                        >
                            <span>{{ category }}</span>
                            <svg
                                class="h-5 w-5 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 14l-7 7m0 0l-7-7m7 7V3"
                                />
                            </svg>
                        </button>

                        <div
                            class="grid grid-cols-2 gap-3 border-t border-gray-200 p-4 sm:grid-cols-3"
                        >
                            <label
                                v-for="tool in tools"
                                :key="tool"
                                class="flex items-center"
                            >
                                <input
                                    type="checkbox"
                                    :checked="
                                        form.preferred_tools.includes(tool)
                                    "
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    @change="toggleTool(tool)"
                                />
                                <span class="ml-2 text-sm text-gray-700">
                                    {{ tool }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Selected Tools Display -->
                <div
                    v-if="form.preferred_tools.length > 0"
                    class="mt-4 flex flex-wrap gap-2"
                >
                    <span
                        v-for="tool in form.preferred_tools"
                        :key="tool"
                        class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800"
                    >
                        {{ tool }}
                        <button
                            type="button"
                            class="text-indigo-600 hover:text-indigo-700"
                            @click="toggleTool(tool)"
                        >
                            ×
                        </button>
                    </span>
                </div>

                <p
                    v-if="form.errors.preferred_tools"
                    class="mt-2 text-sm text-red-600"
                >
                    {{ form.errors.preferred_tools }}
                </p>
            </div>

            <!-- Primary Programming Language -->
            <div>
                <label
                    for="primary_programming_language"
                    class="block text-sm font-medium text-black"
                >
                    Primary Programming Language
                </label>
                <select
                    id="primary_programming_language"
                    v-model="form.primary_programming_language"
                    class="mt-1 block w-full rounded-md border-gray-300 bg-white text-black shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                    <option value="">Select or type language</option>
                    <option
                        v-for="lang in programmingLanguages"
                        :key="lang"
                        :value="lang"
                    >
                        {{ lang }}
                    </option>
                </select>
                <p
                    v-if="form.errors.primary_programming_language"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ form.errors.primary_programming_language }}
                </p>
            </div>

            <div class="flex items-center gap-4">
                <ButtonPrimary
                    type="submit"
                    :disabled="form.processing"
                    :loading="form.processing"
                >
                    Save Tools & Languages
                </ButtonPrimary>
            </div>
        </form>
    </section>
</template>
