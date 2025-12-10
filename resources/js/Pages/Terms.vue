<script setup lang="ts">
import ContainerPage from '@/Components/ContainerPage.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import { COMPANY_INFO } from '@/constants/legal';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head title="Terms of Use" />

    <HeaderPage title="Terms of Use">
        <template #actions>
            <p class="mt-2 text-sm text-gray-600">
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
            <div class="p-6">
                <div
                    data-testid="prose-content"
                    class="prose prose-indigo prose-headings:scroll-mt-20 prose-h2:mb-4 prose-h2:mt-8 prose-h2:text-2xl prose-h2:font-bold prose-h2:text-gray-900 prose-h3:mb-3 prose-h3:mt-6 prose-h3:text-lg prose-h3:font-semibold prose-h3:text-gray-700 prose-p:mb-4 prose-p:text-gray-600 prose-p:leading-relaxed prose-ul:my-4 prose-ul:text-gray-600 prose-strong:text-gray-700 prose-ul:space-y-2 prose-li:leading-relaxed"
                >
                    <h2>1. Agreement to Terms</h2>
                    <p>
                        By accessing and using AI Buddy ("the Service"),
                        operated by
                        {{ COMPANY_INFO.name }} ("we", "us", or "our"), you
                        agree to be bound by these Terms of Use and all
                        applicable laws and regulations. If you do not agree
                        with any of these terms, you are prohibited from using
                        the Service.
                    </p>

                    <h2>2. Description of Service</h2>
                    <p>
                        AI Buddy provides an AI-powered prompt optimisation
                        service that creates tailored prompts based on your
                        personality type (as defined by 16personalities.com) and
                        the task you wish to accomplish. The Service uses
                        artificial intelligence to generate, optimise, and save
                        prompts for your use.
                    </p>

                    <h2>3. Beta/Test Mode Notice</h2>
                    <p>
                        The AI Buddy service is currently in beta/test mode. As
                        a participant in this testing phase, you acknowledge and
                        agree that:
                    </p>
                    <ul>
                        <li>
                            <strong>Data Usage for Improvement:</strong> Any
                            data you choose to enter into the system may be used
                            by us to understand user behaviour, improve the
                            system's functionality, and enhance the quality of
                            responses. We use this data solely for the purpose
                            of developing and refining AI Buddy.
                        </li>
                        <li>
                            <strong>Caution Against Sensitive Data:</strong>
                            Because we are actively testing and improving the
                            system, you should <strong>not</strong> enter any
                            personally sensitive, confidential, or proprietary
                            information. This includes (but is not limited to)
                            passwords, financial information, health data,
                            personal identification numbers, or trade secrets.
                            We cannot guarantee the same level of security and
                            privacy that a fully released service would provide.
                        </li>
                        <li>
                            <strong>Data Retention and Deletion:</strong> All
                            data entered during the beta testing phase will be
                            permanently deleted on 15th January 2026. No data
                            from this testing period will be retained after this
                            date.
                        </li>
                        <li>
                            <strong>Free Access Period:</strong> In exchange for
                            your participation in beta testing, you are granted
                            free access to use the AI Buddy service without
                            limitation until 15th January 2026.
                        </li>
                    </ul>

                    <h2>4. User Accounts</h2>
                    <p>
                        To access certain features of the Service, you must
                        register for an account. You agree to:
                    </p>
                    <ul>
                        <li>
                            Provide accurate, current, and complete information
                            during registration
                        </li>
                        <li>
                            Maintain and promptly update your account
                            information
                        </li>
                        <li>
                            Maintain the security of your password and accept
                            all risks of unauthorised access
                        </li>
                        <li>
                            Notify us immediately of any unauthorised use of
                            your account
                        </li>
                    </ul>
                    <p>
                        We reserve the right to suspend or terminate your
                        account if any information provided proves to be
                        inaccurate, false, or misleading.
                    </p>

                    <h2>5. Acceptable Use</h2>
                    <p>You agree not to use the Service to:</p>
                    <ul>
                        <li>Violate any applicable laws or regulations</li>
                        <li>Infringe upon the rights of others</li>
                        <li>
                            Generate prompts for illegal, harmful, or malicious
                            purposes
                        </li>
                        <li>
                            Attempt to gain unauthorised access to the Service
                            or related systems
                        </li>
                        <li>
                            Interfere with or disrupt the Service or servers
                        </li>
                        <li>Upload or transmit viruses or malicious code</li>
                        <li>
                            Collect or harvest personal information from other
                            users
                        </li>
                        <li>
                            Use the Service for any automated or systematic data
                            collection
                        </li>
                    </ul>

                    <h2>6. Intellectual Property</h2>
                    <p>
                        The Service, including its original content, features,
                        and functionality, is owned by
                        {{ COMPANY_INFO.name }} and is protected by
                        international copyright, trademark, and other
                        intellectual property laws.
                    </p>
                    <p>
                        You retain ownership of the prompts and content you
                        create using the Service. By using the Service, you
                        grant us a licence to use, store, and process your
                        prompts solely for the purpose of providing the Service
                        to you.
                    </p>

                    <h2>7. Third-Party Services</h2>
                    <p>
                        The Service integrates with and relies upon third-party
                        services, including:
                    </p>
                    <ul>
                        <li>
                            <strong>16personalities.com:</strong> For
                            personality type classification
                        </li>
                        <li>
                            <strong>Anthropic (Claude API):</strong> For
                            AI-powered prompt generation and optimisation
                        </li>
                        <li>
                            <strong>OpenAI (Whisper API):</strong> For
                            speech-to-text transcription services
                        </li>
                        <li>
                            <strong>Google OAuth:</strong> For authentication
                            services
                        </li>
                        <li>
                            <strong>Analytics services:</strong> For service
                            improvement and usage tracking
                        </li>
                    </ul>
                    <p>
                        Your use of these third-party services is subject to
                        their respective terms of service and privacy policies.
                        We are not responsible for the practices or content of
                        third-party services.
                    </p>

                    <h2>8. Paid Features and Billing</h2>
                    <p>
                        Certain features of the Service may be provided for a
                        fee. You agree to pay all fees associated with your use
                        of paid features. All fees are non-refundable unless
                        otherwise stated or required by law.
                    </p>
                    <p>
                        We reserve the right to change our fees at any time with
                        reasonable notice. Continued use of paid features after
                        a fee change constitutes acceptance of the new fees.
                    </p>

                    <h2>9. Disclaimer of Warranties</h2>
                    <p>
                        THE SERVICE IS PROVIDED "AS IS" AND "AS AVAILABLE"
                        WITHOUT WARRANTIES OF ANY KIND, EITHER EXPRESS OR
                        IMPLIED. WE DO NOT WARRANT THAT:
                    </p>
                    <ul>
                        <li>
                            The Service will be uninterrupted, secure, or
                            error-free
                        </li>
                        <li>
                            The results obtained from using the Service will be
                            accurate or reliable
                        </li>
                        <li>
                            The quality of any prompts or content will meet your
                            expectations
                        </li>
                        <li>Any errors in the Service will be corrected</li>
                    </ul>

                    <h2>10. Limitation of Liability</h2>
                    <p>
                        TO THE MAXIMUM EXTENT PERMITTED BY LAW,
                        {{ COMPANY_INFO.name }} SHALL NOT BE LIABLE FOR ANY
                        INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR
                        PUNITIVE DAMAGES, OR ANY LOSS OF PROFITS OR REVENUES,
                        WHETHER INCURRED DIRECTLY OR INDIRECTLY, OR ANY LOSS OF
                        DATA, USE, GOODWILL, OR OTHER INTANGIBLE LOSSES
                        RESULTING FROM:
                    </p>
                    <ul>
                        <li>Your use or inability to use the Service</li>
                        <li>
                            Any unauthorised access to or use of our servers
                        </li>
                        <li>Any interruption or cessation of the Service</li>
                        <li>
                            Any bugs, viruses, or other harmful code transmitted
                            through the Service
                        </li>
                        <li>
                            Any errors or omissions in any content or for any
                            loss or damage incurred as a result of the use of
                            any content posted, emailed, transmitted, or
                            otherwise made available through the Service
                        </li>
                    </ul>

                    <h2>11. Indemnification</h2>
                    <p>
                        You agree to indemnify, defend, and hold harmless
                        {{ COMPANY_INFO.name }}, its officers, directors,
                        employees, and agents from any claims, liabilities,
                        damages, losses, and expenses, including reasonable
                        legal fees, arising out of or in any way connected with
                        your access to or use of the Service, or your violation
                        of these Terms.
                    </p>

                    <h2>12. Modifications to the Service</h2>
                    <p>
                        We reserve the right to modify, suspend, or discontinue
                        the Service (or any part thereof) at any time with or
                        without notice. We shall not be liable to you or any
                        third party for any modification, suspension, or
                        discontinuation of the Service.
                    </p>

                    <h2>13. Changes to Terms</h2>
                    <p>
                        We may revise these Terms from time to time. The most
                        current version will always be posted on this page. By
                        continuing to use the Service after changes become
                        effective, you agree to be bound by the revised Terms.
                    </p>

                    <h2>14. Termination</h2>
                    <p>
                        We may terminate or suspend your account and access to
                        the Service immediately, without prior notice or
                        liability, for any reason, including breach of these
                        Terms. Upon termination, your right to use the Service
                        will immediately cease.
                    </p>

                    <h2>15. Governing Law</h2>
                    <p>
                        These Terms shall be governed by and construed in
                        accordance with the laws of England and Wales, without
                        regard to its conflict of law provisions. Any disputes
                        arising under or in connection with these Terms shall be
                        subject to the exclusive jurisdiction of the courts of
                        England and Wales.
                    </p>

                    <h2>16. Severability</h2>
                    <p>
                        If any provision of these Terms is held to be
                        unenforceable or invalid, such provision will be changed
                        and interpreted to accomplish the objectives of such
                        provision to the greatest extent possible under
                        applicable law, and the remaining provisions will
                        continue in full force and effect.
                    </p>

                    <h2>17. Contact Information</h2>
                    <p>
                        If you have any questions about these Terms, please
                        contact us at:
                    </p>
                    <p>
                        <!--                        {{ COMPANY_INFO.name }}<br />-->
                        <!--                        {{ COMPANY_INFO.registeredAddress }}<br />-->
                        {{ COMPANY_INFO.contactEmail }}
                    </p>
                </div>
            </div>
        </div>
    </ContainerPage>
</template>
