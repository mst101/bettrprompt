<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <Head title="Email Verification" />

    <div class="mb-4 text-sm text-gray-600">
        Thanks for signing up! Before getting started, could you verify your
        email address by clicking on the link we just emailed to you? If you
        didn't receive the email, we will gladly send you another.
    </div>

    <div
        class="mb-4 text-sm font-medium text-green-600"
        v-if="verificationLinkSent"
    >
        A new verification link has been sent to the email address you provided
        during registration.
    </div>

    <form @submit.prevent="submit">
        <div class="mt-4 flex items-center justify-between">
            <ButtonPrimary
                type="submit"
                :disabled="form.processing"
                :loading="form.processing"
            >
                Resend Verification Email
            </ButtonPrimary>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden"
                >Log Out</Link
            >
        </div>
    </form>
</template>
