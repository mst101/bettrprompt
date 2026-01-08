<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import { useLocaleRoute } from '@/Composables/useLocaleRoute';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    status?: string;
}>();

defineOptions({
    layout: AppLayout,
});

const { localeRoute } = useLocaleRoute();
const form = useForm({});

const submit = () => {
    form.post(localeRoute('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <Head :title="$t('auth.verifyEmail.title')" />

    <h1 class="mb-6 text-2xl font-bold text-indigo-900">
        {{ $t('auth.verifyEmail.heading') }}
    </h1>

    <div class="mb-4 text-sm text-indigo-600">
        {{ $t('auth.verifyEmail.description') }}
    </div>

    <div
        v-if="verificationLinkSent"
        class="mb-4 text-sm font-medium text-green-600"
    >
        {{ $t('auth.verifyEmail.resent') }}
    </div>

    <form @submit.prevent="submit">
        <div class="mt-4 flex items-center justify-between">
            <ButtonPrimary
                type="submit"
                :disabled="form.processing"
                :loading="form.processing"
            >
                {{ $t('auth.verifyEmail.resend') }}
            </ButtonPrimary>

            <Link
                :href="localeRoute('logout')"
                method="post"
                as="button"
                class="rounded-md text-sm text-indigo-600 underline hover:text-indigo-900 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden"
                >{{ $t('auth.verifyEmail.logout') }}</Link
            >
        </div>
    </form>
</template>
