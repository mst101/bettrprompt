<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import FormCheckbox from '@/Components/Base/Form/FormCheckbox.vue';
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import InputLabel from '@/Components/Base/InputLabel.vue';
import ButtonTrash from '@/Components/Common/ButtonTrash.vue';
import { useAlert } from '@/Composables/ui/useAlert';
import { useNotification } from '@/Composables/ui/useNotification';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Props {
    toolsData: {
        preferredTools: string[];
        primaryProgrammingLanguage: string | null;
    };
}

const props = defineProps<Props>();
const { success, error } = useNotification();

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
    preferredTools: props.toolsData.preferredTools || [],
    primaryProgrammingLanguage:
        props.toolsData.primaryProgrammingLanguage || '',
});

watch(
    () => form.recentlySuccessful,
    (value) => {
        if (value) {
            success('Tools & languages updated successfully');
        }
    },
);

watch(
    () => Object.keys(form.errors).length > 0,
    (hasErrors) => {
        if (hasErrors) {
            const errorMessage = Object.values(form.errors)[0];
            if (typeof errorMessage === 'string') {
                error(errorMessage);
            }
        }
    },
);

const toggleTool = (tool: string) => {
    const index = form.preferredTools.indexOf(tool);
    if (index > -1) {
        form.preferredTools.splice(index, 1);
    } else {
        form.preferredTools.push(tool);
    }
};

const submit = () => {
    form.patch(route('profile.tools.update'), {
        preserveScroll: true,
    });
};

const hasToolsData = computed(() => {
    return (
        form.preferredTools.length > 0 ||
        !!props.toolsData.primaryProgrammingLanguage
    );
});

const { confirm } = useAlert();

const clearTools = async () => {
    const confirmed = await confirm(
        'Are you sure you want to clear all tools and programming languages?',
        'Clear Tools & Technologies',
        { confirmButtonStyle: 'danger', confirmText: 'Clear' },
    );

    if (confirmed) {
        const clearForm = useForm({});
        clearForm.delete(route('profile.tools.clear'), {
            preserveScroll: true,
            onSuccess: () => {
                success('Tools & technologies cleared successfully');
                // Clear the form fields
                form.preferredTools = [];
                form.primaryProgrammingLanguage = '';
            },
            onError: () => {
                error('Failed to clear tools & technologies');
            },
        });
    }
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-indigo-900">
                Tools & Technologies
            </h2>

            <p class="mt-1 text-sm text-indigo-600">
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
                    <p class="mt-1 text-sm text-indigo-600">
                        You've selected
                        {{ form.preferredTools.length }} tool(s)
                    </p>
                </div>

                <!-- Tool Categories -->
                <div class="space-y-4">
                    <div
                        v-for="(tools, category) in toolCategories"
                        :key="category"
                        class="rounded-lg border border-indigo-200 bg-indigo-50 dark:bg-indigo-100"
                    >
                        <div class="border-b border-indigo-200 px-4 py-3">
                            <p class="font-medium text-indigo-900">
                                {{ category }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3">
                            <FormCheckbox
                                v-for="tool in tools"
                                :id="`tool-${tool}`"
                                :key="tool"
                                v-model="form.preferredTools"
                                :value="tool"
                                :label="tool"
                            />
                        </div>
                    </div>
                </div>

                <!-- Selected Tools Display -->
                <div
                    v-if="form.preferredTools.length > 0"
                    class="mt-4 flex flex-wrap gap-2"
                >
                    <span
                        v-for="tool in form.preferredTools"
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
                    v-if="form.errors.preferredTools"
                    class="mt-2 text-sm text-red-600"
                >
                    {{ form.errors.preferredTools }}
                </p>
            </div>

            <!-- Primary Programming Language -->
            <FormSelect
                id="primary-programming-language"
                v-model="form.primaryProgrammingLanguage"
                class="max-w-sm"
                label="Primary Programming Language"
                :options="
                    programmingLanguages.map((lang) => ({
                        value: lang,
                        label: lang,
                    }))
                "
                :error="form.errors.primaryProgrammingLanguage"
                placeholder="Select language"
                show-placeholder
            />

            <div class="flex flex-col items-center gap-4 sm:flex-row">
                <ButtonPrimary
                    type="submit"
                    :disabled="form.processing"
                    :loading="form.processing"
                    icon="download"
                >
                    Save Tools & Languages
                </ButtonPrimary>

                <ButtonTrash
                    v-if="hasToolsData"
                    id="clear-tools-form"
                    label="Clear Tools & Technologies"
                    @clear="clearTools"
                />
            </div>
        </form>
    </section>
</template>
