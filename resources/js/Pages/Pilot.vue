<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head :title="$t('pilot.headTitle')" />

    <div class="relative overflow-hidden bg-white">
        <!-- Decorative background gradient -->
        <div
            aria-hidden="true"
            class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80"
        >
            <div
                class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-linear-to-tr from-indigo-200 via-purple-200 to-indigo-100 opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"
                style="
                    clip-path: polygon(
                        74.1% 44.1%,
                        100% 61.6%,
                        97.5% 26.9%,
                        85.5% 0.1%,
                        80.7% 2%,
                        72.5% 32.5%,
                        60.2% 62.4%,
                        52.4% 68.1%,
                        47.5% 58.3%,
                        45.2% 34.5%,
                        27.5% 76.7%,
                        0.1% 64.9%,
                        17.9% 100%,
                        27.6% 76.8%,
                        76.1% 97.7%,
                        74.1% 44.1%
                    );
                "
            />
        </div>

        <!-- Main content -->
        <div class="mx-auto max-w-4xl px-6 py-16 sm:py-24 lg:px-8 lg:py-32">
            <!-- Hero Section -->
            <div class="text-center">
                <h1
                    class="text-4xl font-bold tracking-tight text-indigo-950 sm:text-5xl lg:text-6xl"
                >
                    {{ $t('pilot.hero.line1') }}<br />
                    {{ $t('pilot.hero.line2') }}
                </h1>
                <p
                    class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-indigo-700 sm:text-xl"
                >
                    {{ $t('pilot.hero.subtitle') }}
                </p>
            </div>

            <!-- The Problem Section -->
            <div class="mt-20 sm:mt-24">
                <div
                    class="rounded-2xl bg-indigo-50 px-6 py-10 sm:px-10 sm:py-12"
                >
                    <h2
                        class="text-2xl font-bold tracking-tight text-indigo-950 sm:text-3xl"
                    >
                        {{ $t('pilot.problem.title') }}
                    </h2>
                    <div
                        class="mt-6 space-y-4 text-base leading-7 text-indigo-800 sm:text-lg"
                    >
                        <p>
                            {{ $t('pilot.problem.paragraph1') }}
                        </p>
                        <p class="font-semibold text-indigo-950">
                            {{ $t('pilot.problem.paragraph2') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- How BettrPrompt Works Differently -->
            <div class="mt-20 sm:mt-24">
                <h2
                    class="text-center text-2xl font-bold tracking-tight text-indigo-950 sm:text-3xl"
                >
                    {{ $t('pilot.how.title') }}
                </h2>
                <div class="mt-10 space-y-8 sm:mt-12">
                    <!-- Step 1 -->
                    <div class="flex gap-4">
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-indigo-600 text-lg font-bold text-white"
                        >
                            1
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-semibold text-indigo-950">
                                {{ $t('pilot.how.steps.step1.title') }}
                            </h3>
                            <p class="mt-2 text-base leading-7 text-indigo-700">
                                {{ $t('pilot.how.steps.step1.description') }}
                            </p>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex gap-4">
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 text-lg font-bold text-white"
                        >
                            2
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-semibold text-indigo-950">
                                {{ $t('pilot.how.steps.step2.title') }}
                            </h3>
                            <p class="mt-2 text-base leading-7 text-indigo-700">
                                {{ $t('pilot.how.steps.step2.description') }}
                            </p>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex gap-4">
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-indigo-600 text-lg font-bold text-white"
                        >
                            3
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-semibold text-indigo-950">
                                {{ $t('pilot.how.steps.step3.title') }}
                            </h3>
                            <p class="mt-2 text-base leading-7 text-indigo-700">
                                {{ $t('pilot.how.steps.step3.description') }}
                            </p>
                        </div>
                    </div>

                    <!-- Result -->
                    <div class="rounded-lg bg-indigo-100 px-6 py-5 sm:px-8">
                        <p class="text-lg font-semibold text-indigo-950">
                            {{ $t('pilot.how.result') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Join the Free Pilot -->
            <div class="mt-20 sm:mt-24">
                <div
                    class="rounded-3xl bg-linear-to-br from-indigo-600 to-purple-600 px-6 py-12 text-center shadow-xl sm:px-12 sm:py-16"
                >
                    <h2
                        class="text-3xl font-bold tracking-tight text-white sm:text-4xl"
                    >
                        {{ $t('pilot.join.title') }}
                    </h2>
                    <p
                        class="mx-auto mt-6 max-w-xl text-lg leading-8 text-indigo-50"
                    >
                        {{ $t('pilot.join.description') }}
                    </p>

                    <div class="mx-auto mt-10 max-w-md space-y-6 text-left">
                        <div>
                            <h3 class="text-lg font-semibold text-white">
                                {{ $t('pilot.join.commitmentTitle') }}
                            </h3>
                            <ul class="mt-3 space-y-2 text-base text-indigo-50">
                                <li class="flex items-start gap-3">
                                    <DynamicIcon
                                        name="check-circle"
                                        class="mt-1 h-5 w-5 flex-shrink-0 text-indigo-200"
                                    />
                                    <span>{{
                                        $t('pilot.join.commitments.use')
                                    }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <DynamicIcon
                                        name="check-circle"
                                        class="mt-1 h-5 w-5 flex-shrink-0 text-indigo-200"
                                    />
                                    <span>{{
                                        $t('pilot.join.commitments.feedback')
                                    }}</span>
                                </li>
                            </ul>
                        </div>

                        <p class="text-base text-indigo-50">
                            {{ $t('pilot.join.note') }}
                        </p>
                    </div>

                    <div class="mt-10">
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="inline-flex items-center gap-2 rounded-lg bg-white px-8 py-4 text-lg font-semibold text-indigo-600 shadow-lg transition-all hover:bg-indigo-50 hover:shadow-xl focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600 focus:outline-none"
                        >
                            {{ $t('pilot.join.ctaRegister') }}
                            <DynamicIcon name="arrow-right" class="h-5 w-5" />
                        </Link>
                        <a
                            v-else
                            href="mailto:hello@bettrprompt.com"
                            class="inline-flex items-center gap-2 rounded-lg bg-white px-8 py-4 text-lg font-semibold text-indigo-600 shadow-lg transition-all hover:bg-indigo-50 hover:shadow-xl focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600 focus:outline-none"
                        >
                            {{ $t('pilot.join.ctaInvite') }}
                            <DynamicIcon name="arrow-right" class="h-5 w-5" />
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer Contact -->
            <div class="mt-16 text-center">
                <p class="text-sm text-indigo-600">
                    {{ $t('pilot.footer.questions') }}
                    <a
                        href="mailto:hello@bettrprompt.com"
                        class="font-semibold underline hover:text-indigo-700"
                    >
                        {{ $t('pilot.footer.contact') }}
                    </a>
                </p>
            </div>
        </div>

        <!-- Bottom decorative gradient -->
        <div
            aria-hidden="true"
            class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]"
        >
            <div
                class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-linear-to-tr from-purple-200 via-indigo-200 to-purple-100 opacity-30 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]"
                style="
                    clip-path: polygon(
                        74.1% 44.1%,
                        100% 61.6%,
                        97.5% 26.9%,
                        85.5% 0.1%,
                        80.7% 2%,
                        72.5% 32.5%,
                        60.2% 62.4%,
                        52.4% 68.1%,
                        47.5% 58.3%,
                        45.2% 34.5%,
                        27.5% 76.7%,
                        0.1% 64.9%,
                        17.9% 100%,
                        27.6% 76.8%,
                        76.1% 97.7%,
                        74.1% 44.1%
                    );
                "
            />
        </div>
    </div>
</template>
