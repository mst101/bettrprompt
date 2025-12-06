\
<script setup lang="ts">
import ContainerPage from '@/Components/ContainerPage.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import { COMPANY_INFO, LEGAL_URLS } from '@/constants/legal';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head title="Privacy Policy" />

    <HeaderPage title="Privacy Policy">
        <template #actions>
            <p class="mt-2 w-1/2 text-sm text-gray-600">
                Last updated:
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
        <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
            <div
                data-testid="prose-content"
                class="prose prose-indigo prose-headings:scroll-mt-20 prose-h2:mb-4 prose-h2:mt-8 prose-h2:text-2xl prose-h2:font-bold prose-h2:text-gray-900 prose-h3:mb-3 prose-h3:mt-6 prose-h3:text-lg prose-h3:font-semibold prose-h3:text-gray-700 prose-p:mb-4 prose-p:text-gray-600 prose-p:leading-relaxed prose-ul:my-4 prose-ul:text-gray-600 prose-strong:text-gray-700 prose-ul:space-y-2 prose-li:leading-relaxed mt-8 max-w-none px-6"
            >
                <h2>1. Introduction</h2>
                <p>
                    {{ COMPANY_INFO.name }} ("we", "us", or "our") operates AI
                    Buddy (the "Service"). This Privacy Policy explains how we
                    collect, use, disclose, and safeguard your personal
                    information when you use our Service.
                </p>
                <p>
                    We are committed to protecting your privacy and ensuring
                    compliance with the UK General Data Protection Regulation
                    (UK GDPR) and the Data Protection Act 2018.
                </p>

                <h2>2. Data Controller</h2>
                <p>For the purposes of UK GDPR, the data controller is:</p>
                <p>
                    {{ COMPANY_INFO.name }}<br />
                    {{ COMPANY_INFO.registeredAddress }}<br />
                    Email: {{ COMPANY_INFO.contactEmail }}
                </p>

                <h2>3. Information We Collect</h2>

                <h3>3.1 Information You Provide</h3>
                <p>
                    We collect information that you voluntarily provide when
                    using the Service:
                </p>
                <ul>
                    <li>
                        <strong>Account Information:</strong> Name, email
                        address, password (encrypted)
                    </li>
                    <li>
                        <strong>Personality Information:</strong> Your
                        16personalities.com personality type and trait
                        percentages
                    </li>
                    <li>
                        <strong>Prompt Data:</strong> Tasks you submit and
                        optimised prompts generated for you
                    </li>
                    <li>
                        <strong>Profile Information:</strong> Any additional
                        information you choose to add to your profile
                    </li>
                    <li>
                        <strong>Communication Data:</strong> Information you
                        provide when contacting us for support
                    </li>
                </ul>

                <h3>3.2 Automatically Collected Information</h3>
                <p>When you use the Service, we automatically collect:</p>
                <ul>
                    <li>
                        <strong>Usage Data:</strong> Pages visited, features
                        used, time spent, click patterns
                    </li>
                    <li>
                        <strong>Device Information:</strong> IP address, browser
                        type, operating system, device identifiers
                    </li>
                    <li>
                        <strong>Log Data:</strong> Server logs, error reports,
                        performance data
                    </li>
                    <li>
                        <strong>Cookies and Similar Technologies:</strong>
                        Session cookies, preference cookies, analytics cookies
                    </li>
                </ul>

                <h3>3.3 Information from Third Parties</h3>
                <ul>
                    <li>
                        <strong>Google OAuth:</strong> If you use Google
                        sign-in, we receive your name, email address, and
                        profile picture from Google
                    </li>
                    <li>
                        <strong>Payment Processors:</strong> If you use paid
                        features, we receive payment confirmation data (but not
                        your payment card details)
                    </li>
                </ul>

                <h2>4. Legal Basis for Processing</h2>
                <p>
                    Under UK GDPR, we process your personal data on the
                    following legal bases:
                </p>
                <ul>
                    <li>
                        <strong>Contractual Necessity:</strong> To provide the
                        Service you've requested
                    </li>
                    <li>
                        <strong>Legitimate Interests:</strong> To improve the
                        Service, prevent fraud, and ensure security
                    </li>
                    <li>
                        <strong>Consent:</strong> For marketing communications
                        and non-essential cookies (where required)
                    </li>
                    <li>
                        <strong>Legal Obligation:</strong> To comply with
                        applicable laws and regulations
                    </li>
                </ul>

                <h2>5. How We Use Your Information</h2>
                <p>We use your information to:</p>
                <ul>
                    <li>Provide, maintain, and improve the Service</li>
                    <li>
                        Generate personalised AI prompts based on your
                        personality type and tasks
                    </li>
                    <li>
                        Authenticate your account and manage your preferences
                    </li>
                    <li>Process payments for paid features</li>
                    <li>Send you service-related communications and updates</li>
                    <li>Respond to your support requests and enquiries</li>
                    <li>Analyse usage patterns to improve user experience</li>
                    <li>
                        Detect, prevent, and address technical issues and
                        security threats
                    </li>
                    <li>
                        Comply with legal obligations and enforce our Terms of
                        Use
                    </li>
                    <li>
                        Send you marketing communications (with your consent)
                    </li>
                </ul>

                <h2>6. Third-Party Services and Data Sharing</h2>
                <p>
                    We share your data with the following third-party services:
                </p>

                <h3>6.1 Essential Service Providers</h3>
                <ul>
                    <li>
                        <strong>Anthropic (Claude API):</strong> We send your
                        task descriptions and personality information to
                        Anthropic's Claude API to generate optimised prompts.
                        Anthropic's data processing is governed by their
                        <a
                            href="https://www.anthropic.com/legal/privacy"
                            target="_blank"
                            rel="noopener"
                            class="text-indigo-600 hover:text-indigo-800"
                            >Privacy Policy</a
                        >
                        and
                        <a
                            href="https://www.anthropic.com/legal/consumer-terms"
                            target="_blank"
                            rel="noopener"
                            class="text-indigo-600 hover:text-indigo-800"
                            >Terms of Service</a
                        >.
                    </li>
                    <li>
                        <strong>OpenAI (Whisper API):</strong> When you use
                        voice input features, we send your audio to OpenAI's
                        Whisper API for speech-to-text transcription. OpenAI's
                        data processing is governed by their
                        <a
                            href="https://openai.com/policies/privacy-policy"
                            target="_blank"
                            rel="noopener"
                            class="text-indigo-600 hover:text-indigo-800"
                            >Privacy Policy</a
                        >
                        and
                        <a
                            href="https://openai.com/policies/terms-of-use"
                            target="_blank"
                            rel="noopener"
                            class="text-indigo-600 hover:text-indigo-800"
                            >Terms of Use</a
                        >.
                    </li>
                    <li>
                        <strong>Google OAuth:</strong> For authentication
                        services, subject to Google's
                        <a
                            href="https://policies.google.com/privacy"
                            target="_blank"
                            rel="noopener"
                            class="text-indigo-600 hover:text-indigo-800"
                            >Privacy Policy</a
                        >.
                    </li>
                    <li>
                        <strong>Hosting Provider:</strong> Our servers and
                        databases are hosted securely with
                        {{ COMPANY_INFO.hostingProvider }}.
                    </li>
                </ul>

                <h3>6.2 Analytics and Performance</h3>
                <ul>
                    <li>
                        <strong>Analytics Services:</strong> We use analytics
                        tools to understand how users interact with the Service.
                        These services may collect anonymised or pseudonymised
                        data.
                    </li>
                </ul>

                <h3>6.3 Payment Processors</h3>
                <ul>
                    <li>
                        <strong>Payment Services:</strong> If you purchase paid
                        features, your payment information is processed directly
                        by our payment processor. We do not store your payment
                        card details.
                    </li>
                </ul>

                <h3>6.4 Other Disclosures</h3>
                <p>We may also disclose your information:</p>
                <ul>
                    <li>
                        To comply with legal obligations, court orders, or
                        governmental requests
                    </li>
                    <li>
                        To protect our rights, property, or safety, and that of
                        our users
                    </li>
                    <li>
                        In connection with a merger, acquisition, or sale of
                        assets (with notice to you)
                    </li>
                    <li>With your explicit consent</li>
                </ul>

                <h2>7. Data Retention</h2>
                <p>
                    We retain your personal data only for as long as necessary
                    for the purposes set out in this Privacy Policy:
                </p>
                <ul>
                    <li>
                        <strong>Account Data:</strong> Retained while your
                        account is active and for up to 90 days after deletion
                    </li>
                    <li>
                        <strong>Prompt History:</strong> Retained while your
                        account is active, unless you delete specific prompts
                    </li>
                    <li>
                        <strong>Log Data:</strong> Typically retained for up to
                        90 days
                    </li>
                    <li>
                        <strong>Legal/Financial Records:</strong>
                        Retained as required by law (e.g., 6-7 years for
                        financial records)
                    </li>
                </ul>

                <h2>8. Your Rights Under UK GDPR</h2>
                <p>
                    You have the following rights regarding your personal data:
                </p>
                <ul>
                    <li>
                        <strong>Right of Access:</strong> Request a copy of the
                        personal data we hold about you
                    </li>
                    <li>
                        <strong>Right to Rectification:</strong> Correct
                        inaccurate or incomplete data
                    </li>
                    <li>
                        <strong>Right to Erasure:</strong> Request deletion of
                        your personal data ("right to be forgotten")
                    </li>
                    <li>
                        <strong>Right to Restrict Processing:</strong>
                        Limit how we use your data
                    </li>
                    <li>
                        <strong>Right to Data Portability:</strong>
                        Receive your data in a structured, machine-readable
                        format
                    </li>
                    <li>
                        <strong>Right to Object:</strong> Object to processing
                        based on legitimate interests or for marketing purposes
                    </li>
                    <li>
                        <strong>Right to Withdraw Consent:</strong>
                        Where processing is based on consent
                    </li>
                    <li>
                        <strong>Right to Complain:</strong> Lodge a complaint
                        with the Information Commissioner's Office (ICO)
                    </li>
                </ul>
                <p>
                    To exercise these rights, please contact us at
                    {{ COMPANY_INFO.contactEmail }}.
                </p>

                <h2>9. Data Security</h2>
                <p>
                    We implement appropriate technical and organisational
                    measures to protect your personal data:
                </p>
                <ul>
                    <li>
                        Encryption of data in transit (HTTPS/TLS) and at rest
                    </li>
                    <li>Regular security assessments and updates</li>
                    <li>Access controls and authentication mechanisms</li>
                    <li>Employee training on data protection</li>
                    <li>Incident response procedures</li>
                </ul>
                <p>
                    However, no method of transmission over the internet is 100%
                    secure. Whilst we strive to protect your data, we cannot
                    guarantee absolute security.
                </p>

                <h2>10. International Data Transfers</h2>
                <p>
                    Some of our third-party service providers (such as OpenAI)
                    may be located outside the United Kingdom. Where we transfer
                    data internationally, we ensure appropriate safeguards are
                    in place, such as:
                </p>
                <ul>
                    <li>
                        Standard Contractual Clauses (SCCs) approved by the UK
                        authorities
                    </li>
                    <li>Adequacy decisions by the UK Government</li>
                    <li>Other legally recognised transfer mechanisms</li>
                </ul>

                <h2>11. Children's Privacy</h2>
                <p>
                    The Service is not intended for children under 13 years of
                    age. We do not knowingly collect personal information from
                    children under 13. If you believe we have collected
                    information from a child under 13, please contact us
                    immediately.
                </p>

                <h2>12. Cookies and Tracking Technologies</h2>
                <p>We use cookies and similar technologies to:</p>
                <ul>
                    <li>
                        <strong>Essential Cookies:</strong> Required for the
                        Service to function (e.g., session management,
                        authentication)
                    </li>
                    <li>
                        <strong>Performance Cookies:</strong> Help us understand
                        how users interact with the Service
                    </li>
                    <li>
                        <strong>Functional Cookies:</strong> Remember your
                        preferences and settings
                    </li>
                </ul>
                <p>
                    You can control cookie preferences through your browser
                    settings. However, disabling essential cookies may affect
                    the functionality of the Service.
                </p>

                <h2>13. Marketing Communications</h2>
                <p>
                    With your consent, we may send you marketing emails about
                    new features, updates, and offers. You can opt out at any
                    time by:
                </p>
                <ul>
                    <li>
                        Clicking the "unsubscribe" link in any marketing email
                    </li>
                    <li>Updating your preferences in your account settings</li>
                    <li>Contacting us at {{ COMPANY_INFO.contactEmail }}</li>
                </ul>
                <p>
                    Please note that you will continue to receive essential
                    service-related communications regardless of your marketing
                    preferences.
                </p>

                <h2>14. Changes to This Privacy Policy</h2>
                <p>
                    We may update this Privacy Policy from time to time. We will
                    notify you of significant changes by:
                </p>
                <ul>
                    <li>Posting the new Privacy Policy on this page</li>
                    <li>Updating the "Last updated" date</li>
                    <li>Sending an email notification for material changes</li>
                </ul>
                <p>
                    Your continued use of the Service after changes become
                    effective constitutes acceptance of the updated Privacy
                    Policy.
                </p>

                <h2>15. Contact Us</h2>
                <p>
                    If you have questions, concerns, or wish to exercise your
                    rights, please contact us:
                </p>
                <p>
                    <strong>Data Protection Enquiries:</strong><br />
                    {{ COMPANY_INFO.name }}<br />
                    {{ COMPANY_INFO.registeredAddress }}<br />
                    Email: {{ COMPANY_INFO.contactEmail }}
                </p>
                <p>
                    <strong>Supervisory Authority:</strong><br />
                    If you are not satisfied with our response, you have the
                    right to lodge a complaint with the Information
                    Commissioner's Office (ICO):<br />
                    Website:
                    <a
                        :href="LEGAL_URLS.ico"
                        target="_blank"
                        rel="noopener"
                        class="text-indigo-600 hover:text-indigo-800"
                        >{{ LEGAL_URLS.ico }}</a
                    ><br />
                    Telephone: {{ LEGAL_URLS.icoPhone }}
                </p>
            </div>
        </div>
    </ContainerPage>
</template>
