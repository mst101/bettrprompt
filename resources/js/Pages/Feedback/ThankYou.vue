<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import Card from '@/Components/Card.vue';
import ContainerPage from '@/Components/ContainerPage.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

interface Pdf {
    name: string;
    filename: string;
}

interface Props {
    personalityType: string | null;
    personalityTypeName: string | null;
    availablePdfs: Pdf[];
    referralUrl: string;
}

const copied = ref(false);

const copyToClipboard = async () => {
    try {
        await navigator.clipboard.writeText(props.referralUrl);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};
</script>

<template>
    <Head title="Thank You for Your Feedback" />

    <HeaderPage title="Thank You!" />

    <ContainerPage spacing>
        <!-- Thank you message -->
        <Card>
            <div class="text-center">
                <DynamicIcon
                    name="check-circle"
                    class="mx-auto h-16 w-16 text-green-600"
                />
                <h2 class="mt-4 text-2xl font-semibold text-gray-900">
                    Thank you for your feedback!
                </h2>
                <p class="mt-2 text-gray-600">
                    Your insights help us improve the service for everyone.
                </p>
            </div>
        </Card>

        <!-- PDF Downloads Section -->
        <Card v-if="personalityTypeName && availablePdfs.length > 0">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">
                Your Personality Resources
            </h3>
            <p class="mb-4 text-sm text-gray-600">
                As a {{ personalityType }} ({{ personalityTypeName }}), we've
                prepared these guides specifically for you:
            </p>

            <div class="space-y-3">
                <a
                    v-for="pdf in availablePdfs"
                    :key="pdf.filename"
                    :href="route('feedback.download-pdf', pdf.filename)"
                    class="flex items-center justify-between rounded-lg border border-gray-200 p-4 transition hover:border-indigo-500 hover:bg-indigo-50"
                >
                    <div class="flex items-center">
                        <DynamicIcon
                            name="document"
                            class="mr-3 h-6 w-6 text-indigo-600"
                        />
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ pdf.name }}
                            </p>
                            <p class="text-sm text-gray-500">PDF Guide</p>
                        </div>
                    </div>
                    <DynamicIcon
                        name="download"
                        class="h-5 w-5 text-gray-400"
                    />
                </a>
            </div>
        </Card>

        <!-- Referral Section -->
        <Card>
            <h3 class="mb-2 text-lg font-semibold text-gray-900">
                Invite a Friend to Trial
            </h3>
            <p class="mb-4 text-sm text-gray-600">
                Know someone who could benefit from personalised AI prompts?
                Share your unique referral link with them. They can trial the
                service until
                <strong>15th January 2026</strong>.
            </p>

            <div class="rounded-lg bg-gray-50 p-4">
                <label
                    for="referral-url"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
                    Your Referral Link
                </label>
                <div class="flex items-center gap-2">
                    <input
                        id="referral-url"
                        type="text"
                        :value="referralUrl"
                        readonly
                        class="flex-1 rounded-lg border-gray-300 bg-white px-4 py-2 font-mono text-sm text-gray-900"
                    />
                    <ButtonPrimary type="button" @click="copyToClipboard">
                        <DynamicIcon
                            v-if="copied"
                            name="check"
                            class="mr-2 h-5 w-5"
                        />
                        <DynamicIcon
                            v-else
                            name="clipboard"
                            class="mr-2 h-5 w-5"
                        />
                        {{ copied ? 'Copied!' : 'Copy' }}
                    </ButtonPrimary>
                </div>
            </div>

            <div class="mt-4 rounded-lg bg-blue-50 p-4">
                <div class="flex">
                    <DynamicIcon
                        name="information-circle"
                        class="mr-3 h-5 w-5 flex-shrink-0 text-blue-600"
                    />
                    <div class="text-sm text-blue-800">
                        <p class="font-medium">Trial Deadline</p>
                        <p class="mt-1">
                            Friends who sign up using your link can trial the
                            service until <strong>15th January 2026</strong>.
                            Share via email, WhatsApp, or any other direct
                            messaging platform, but please don't post publicly
                            (as this is still a trial service).
                        </p>
                    </div>
                </div>
            </div>
        </Card>
    </ContainerPage>
</template>
