<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import CollapsibleSection from '@/Components/Base/CollapsibleSection.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import { useNotification } from '@/Composables/ui/useNotification';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();

const user = usePage().props.auth!.user!;
const { success, error } = useNotification();

const form = useForm({
    name: user.name,
    email: user.email,
});

watch(
    () => form.recentlySuccessful,
    (value) => {
        if (value) {
            success('Profile information updated successfully');
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
</script>

<template>
    <section>
        <CollapsibleSection
            title="Profile Information"
            subtitle="Update your name and email address."
            data-testid="profile-information"
        >
            <form
                class="space-y-6"
                @submit.prevent="form.patch(route('profile.update'))"
            >
                <FormInput
                    id="name"
                    v-model="form.name"
                    class="max-w-sm"
                    label="Name"
                    type="text"
                    :error="form.errors.name"
                    required
                    autocomplete="name"
                />

                <FormInput
                    id="email"
                    v-model="form.email"
                    class="max-w-sm"
                    label="Email"
                    type="email"
                    :error="form.errors.email"
                    required
                    autocomplete="username"
                />

                <div v-if="mustVerifyEmail && user.emailVerifiedAt === null">
                    <p class="mt-2 text-sm text-indigo-800">
                        Your email address is unverified.
                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="rounded-md text-sm text-indigo-600 underline hover:text-indigo-900 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden"
                        >
                            Click here to re-send the verification email.
                        </Link>
                    </p>

                    <div
                        v-show="status === 'verification-link-sent'"
                        class="mt-2 text-sm font-medium text-green-600"
                    >
                        A new verification link has been sent to your email
                        address.
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <ButtonPrimary
                        type="submit"
                        :disabled="form.processing"
                        :loading="form.processing"
                        icon="download"
                    >
                        Save
                    </ButtonPrimary>
                </div>
            </form>
        </CollapsibleSection>
    </section>
</template>
