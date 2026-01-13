<script setup lang="ts">
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { COOKIE_CATEGORIES } from '@/Constants/cookies';
import { COMPANY_INFO } from '@/Constants/legal';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n({ useScope: 'global' });

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head :title="t('legal.cookies.title')" />

    <HeaderPage :title="t('legal.cookies.title')">
        <template #actions>
            <p class="mt-2 text-sm text-indigo-700">
                {{ t('legal.lastUpdated') }}:
                {{
                    new Date().toLocaleDateString('en-GB', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                    })
                }}
            </p>
        </template>
    </HeaderPage>

    <ContainerPage>
        <div class="overflow-hidden bg-indigo-50 shadow-xs sm:rounded-lg">
            <div
                class="prose prose-indigo prose-headings:scroll-mt-20 prose-h2:mb-4 prose-h2:mt-8 prose-h2:text-2xl prose-h2:font-bold prose-h2:text-indigo-900 prose-h3:mb-3 prose-h3:mt-6 prose-h3:text-lg prose-h3:font-semibold prose-h3:text-indigo-700 prose-p:mb-4 prose-p:text-indigo-800 prose-p:leading-relaxed prose-ul:my-4 prose-ul:text-indigo-700 prose-strong:text-indigo-700 prose-ul:space-y-2 prose-li:leading-relaxed mt-8 max-w-none px-6"
            >
                <h2>1. What Are Cookies?</h2>
                <p>
                    Cookies are small text files that are placed on your device
                    (computer, smartphone, or tablet) when you visit a website.
                    They are widely used to make websites work more efficiently
                    and provide information to website owners.
                </p>
                <p>
                    Cookies allow websites to remember your actions and
                    preferences (such as login details, language selection, and
                    other display preferences) over a period of time, so you
                    don't have to keep re-entering them whenever you return to
                    the site or browse from one page to another.
                </p>

                <h2>2. How We Use Cookies</h2>
                <p>{{ COMPANY_INFO.name }} uses cookies to:</p>
                <ul>
                    <li>Keep you signed in to your account</li>
                    <li>Remember your preferences and settings</li>
                    <li>Protect against security threats and abuse</li>
                    <li>Understand how you use our Service to improve it</li>
                    <li>Provide personalised content and features</li>
                </ul>

                <h2>3. Types of Cookies We Use</h2>
                <p>
                    We categorise cookies based on their purpose and provide you
                    with control over non-essential cookies.
                </p>

                <!-- Essential Cookies -->
                <div
                    class="not-prose my-6 rounded-lg border border-indigo-100 bg-indigo-50 p-6 dark:bg-indigo-100"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-indigo-900">
                                {{ COOKIE_CATEGORIES.essential.name }}
                            </h3>
                            <span
                                class="mt-1 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                            >
                                {{ t('legal.cookies.alwaysActive') }}
                            </span>
                        </div>
                    </div>
                    <p class="mt-3 text-sm text-indigo-700">
                        {{ COOKIE_CATEGORIES.essential.description }}
                    </p>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="cookie in COOKIE_CATEGORIES.essential
                                .cookies"
                            :key="cookie.name"
                            class="rounded-sm border border-indigo-100 bg-white p-3 dark:bg-indigo-50"
                        >
                            <div class="text-sm font-medium text-indigo-900">
                                {{ cookie.name }}
                            </div>
                            <div class="mt-1 text-xs text-indigo-700">
                                {{ cookie.purpose }}
                            </div>
                            <div class="mt-1 text-xs text-indigo-700">
                                Duration: {{ cookie.duration }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Functional Cookies -->
                <div
                    class="not-prose my-6 rounded-lg border border-indigo-100 bg-indigo-50 p-6 dark:bg-indigo-100"
                >
                    <h3 class="text-lg font-semibold text-indigo-900">
                        {{ COOKIE_CATEGORIES.functional.name }}
                    </h3>
                    <p class="mt-3 text-sm text-indigo-700">
                        {{ COOKIE_CATEGORIES.functional.description }}
                    </p>
                    <div class="mt-4 space-y-3">
                        <div
                            v-if="
                                COOKIE_CATEGORIES.functional.cookies.length ===
                                0
                            "
                            class="rounded-sm border border-indigo-100 bg-white p-3 text-xs text-indigo-700 dark:bg-indigo-50"
                        >
                            We do not currently set any functional cookies.
                        </div>
                        <template v-else>
                            <div
                                v-for="cookie in COOKIE_CATEGORIES.functional
                                    .cookies"
                                :key="cookie.name"
                                class="rounded-sm border border-indigo-100 bg-white p-3 dark:bg-indigo-50"
                            >
                                <div
                                    class="text-sm font-medium text-indigo-900"
                                >
                                    {{ cookie.name }}
                                </div>
                                <div class="mt-1 text-xs text-indigo-700">
                                    {{ cookie.purpose }}
                                </div>
                                <div class="mt-1 text-xs text-indigo-700">
                                    Duration: {{ cookie.duration }}
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Analytics Cookies -->
                <div
                    class="not-prose my-6 rounded-lg border border-indigo-100 bg-indigo-50 p-6 dark:bg-indigo-100"
                >
                    <h3 class="text-lg font-semibold text-indigo-900">
                        {{ COOKIE_CATEGORIES.analytics.name }}
                    </h3>
                    <p class="mt-3 text-sm text-indigo-700">
                        {{ COOKIE_CATEGORIES.analytics.description }}
                    </p>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="cookie in COOKIE_CATEGORIES.analytics
                                .cookies"
                            :key="cookie.name"
                            class="rounded-sm border border-indigo-100 bg-white p-3 dark:bg-indigo-50"
                        >
                            <div class="text-sm font-medium text-indigo-900">
                                {{ cookie.name }}
                            </div>
                            <div class="mt-1 text-xs text-indigo-700">
                                {{ cookie.purpose }}
                            </div>
                            <div class="mt-1 text-xs text-indigo-700">
                                Duration: {{ cookie.duration }}
                            </div>
                        </div>
                    </div>
                </div>

                <h2>4. Third-Party Cookies</h2>
                <p>
                    Some cookies are placed by third-party services that appear
                    on our pages:
                </p>
                <ul>
                    <li>
                        <strong>Google OAuth:</strong> When you use Google
                        sign-in, Google may set cookies according to their
                        privacy policy
                    </li>
                    <li>
                        <strong>FullStory:</strong> In production, we use
                        FullStory to understand how visitors interact with the
                        Service and to improve usability. FullStory may set
                        cookies such as <code>_fs_uid</code> and
                        <code>_fs_lq</code>.
                    </li>
                </ul>
                <p>
                    We do not control these third-party cookies. Please check
                    the relevant third-party websites for more information about
                    their cookies and how to manage them.
                </p>

                <h2>5. How to Control Cookies</h2>
                <p>
                    You have the right to decide whether to accept or reject
                    cookies (except essential cookies, which are necessary for
                    the Service to function).
                </p>

                <h3>5.1 Cookie Banner</h3>
                <p>
                    When you first visit our Service, you will see a cookie
                    banner allowing you to:
                </p>
                <ul>
                    <li><strong>Accept All:</strong> Consent to all cookies</li>
                    <li>
                        <strong>Reject All:</strong> Reject all non-essential
                        cookies
                    </li>
                    <li>
                        <strong>Customise:</strong> Choose which categories of
                        cookies to accept
                    </li>
                </ul>

                <h3>5.2 Cookie Settings</h3>
                <p>
                    You can change your cookie preferences at any time by
                    clicking the "Cookie Settings" link in the footer of any
                    page. This will open a modal where you can review and update
                    your choices.
                </p>

                <h3>5.3 Browser Settings</h3>
                <p>
                    Most web browsers allow you to control cookies through their
                    settings preferences. However, if you limit the ability of
                    websites to set cookies, you may worsen your overall user
                    experience, as some features may no longer function
                    properly.
                </p>
                <p>
                    To find out more about cookies, including how to see what
                    cookies have been set and how to manage and delete them,
                    visit:
                </p>
                <ul>
                    <li>
                        <a
                            href="https://www.allaboutcookies.org/"
                            target="_blank"
                            rel="noopener"
                            class="text-indigo-700 hover:text-indigo-800"
                            >www.allaboutcookies.org</a
                        >
                    </li>
                    <li>
                        <a
                            href="https://www.youronlinechoices.com/"
                            target="_blank"
                            rel="noopener"
                            class="text-indigo-700 hover:text-indigo-800"
                            >www.youronlinechoices.com</a
                        >
                    </li>
                </ul>

                <h2>6. Do Not Track Signals</h2>
                <p>
                    Some browsers incorporate a "Do Not Track" (DNT) feature
                    that signals to websites that you do not want to have your
                    online activity tracked. Our Service currently does not
                    respond to DNT signals.
                </p>

                <h2>7. Changes to This Cookie Policy</h2>
                <p>
                    We may update this Cookie Policy from time to time to
                    reflect changes in our practices or for other operational,
                    legal, or regulatory reasons. We will notify you of any
                    material changes by posting the new Cookie Policy on this
                    page and updating the "Last updated" date.
                </p>

                <h2>8. Contact Us</h2>
                <p>
                    If you have questions about our use of cookies, please
                    contact us:
                </p>
                <p>
                    {{ COMPANY_INFO.name }}<br />
                    {{ COMPANY_INFO.registeredAddress }}<br />
                    Email: {{ COMPANY_INFO.contactEmail }}
                </p>
            </div>
        </div>
    </ContainerPage>
</template>
