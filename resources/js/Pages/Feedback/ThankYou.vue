<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

interface Props {
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
    <Head :title="$t('feedback.thankYou.title')" />

    <HeaderPage :title="$t('feedback.thankYou.heading')" />

    <ContainerPage spacing>
        <!-- Thank you message -->
        <Card>
            <div class="text-center">
                <DynamicIcon
                    name="check-circle"
                    class="mx-auto h-16 w-16 text-green-600"
                />
                <h1
                    class="mt-4 text-lg font-semibold text-indigo-900 sm:text-2xl"
                >
                    {{ $t('feedback.thankYou.message.title') }}
                </h1>
                <p class="mt-2 text-xs text-indigo-600 sm:text-sm">
                    {{ $t('feedback.thankYou.message.subtitle') }}
                </p>
            </div>
        </Card>

        <!-- Referral Section -->
        <Card>
            <h2 class="mb-2 text-base font-semibold text-indigo-900 sm:text-lg">
                {{ $t('feedback.thankYou.referral.title') }}
            </h2>
            <p class="mb-4 text-xs text-indigo-600 sm:text-sm">
                {{ $t('feedback.thankYou.referral.descriptionPrefix') }}
                <strong>{{ $t('feedback.thankYou.referral.deadline') }}</strong
                >.
            </p>

            <div class="rounded-lg bg-indigo-50 p-4">
                <label
                    for="referral-url"
                    class="mb-2 block text-xs font-medium text-indigo-700 sm:text-sm"
                >
                    {{ $t('feedback.thankYou.referral.linkLabel') }}
                </label>
                <div class="flex items-center gap-2">
                    <input
                        id="referral-url"
                        type="text"
                        :value="referralUrl"
                        readonly
                        class="flex-1 rounded-lg border-indigo-100 bg-white px-4 py-2 font-mono text-xs text-indigo-900 sm:text-sm"
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
                        {{
                            copied
                                ? $t('feedback.thankYou.actions.copied')
                                : $t('feedback.thankYou.actions.copy')
                        }}
                    </ButtonPrimary>
                </div>
            </div>

            <div class="mt-4 rounded-lg bg-blue-50 p-4">
                <div class="flex">
                    <DynamicIcon
                        name="information-circle"
                        class="mr-3 h-5 w-5 shrink-0 text-blue-600"
                    />
                    <div class="text-xs text-blue-800 sm:text-sm">
                        <p class="font-medium">
                            {{ $t('feedback.thankYou.trial.title') }}
                        </p>
                        <p class="mt-1">
                            {{
                                $t('feedback.thankYou.trial.descriptionPrefix')
                            }}
                            <strong>{{
                                $t('feedback.thankYou.referral.deadline')
                            }}</strong
                            >.
                            {{
                                $t('feedback.thankYou.trial.descriptionSuffix')
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </Card>
    </ContainerPage>
</template>
