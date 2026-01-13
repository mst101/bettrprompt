import 'flag-icons/css/flag-icons.min.css';
import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { createApp, DefineComponent, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { useNotification } from './Composables/ui/useNotification';
import { createCountryRoutePlugin } from './Plugins/countryRoutePlugin';
import { getCookie } from './Utils/cookies';
import { i18n, initializeI18n, setLocale, type LocaleCode } from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pinia = createPinia();

type AuthUser = {
    id: number;
    email: string | null;
    name: string | null;
    personality_type: string | null;
    created_at: string;
};

type FullStoryAuth = {
    user?: AuthUser | null;
} | null;

// Fullstory visitor identification
function identifyVisitorInFullstory(auth: FullStoryAuth) {
    if (!window.FS) {
        return;
    }

    const visitorId = getCookie('visitor_id');

    if (auth?.user) {
        // Registered user - identify with user ID and link to visitor
        window.FS.identify(auth.user.id.toString(), {
            email: auth.user.email,
            displayName: auth.user.name,
            personalityType: auth.user.personality_type,
            registrationDate: auth.user.created_at,
            visitorId: visitorId || undefined,
        });
    } else if (visitorId) {
        // Anonymous visitor - identify with visitor ID
        window.FS.identify(visitorId, {
            isGuest: true,
        });
    }
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    async setup({ el, App, props, plugin }) {
        // Initialize i18n and set locale from server-side props
        const locale =
            (props.initialPage.props.locale as LocaleCode) || 'en-US';
        await initializeI18n();
        await setLocale(locale);

        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue)
            .use(i18n)
            .use(createCountryRoutePlugin())
            .mount(el);

        // Identify visitor in Fullstory on initial load
        identifyVisitorInFullstory(
            props.initialPage.props.auth as FullStoryAuth,
        );

        return app;
    },
    progress: {
        color: '#4B5563',
    },
});

// Global error handler for 419 Page Expired errors
router.on('invalid', (event) => {
    const response = event.detail?.response;

    // Check if the error is a 419 (Page Expired / CSRF token mismatch)
    if (response?.status === 419) {
        console.warn('Session expired (419 error)');

        // Emit a custom event that components (like login form) can listen to
        // This allows login form to retry with a fresh token instead of reloading
        window.dispatchEvent(
            new CustomEvent('csrf-token-expired', {
                detail: { response },
            }),
        );

        // Fallback: if no component handles the event within 2 seconds, reload the page
        setTimeout(() => {
            // Only reload if the event wasn't handled by a component
            if (!window.csrfTokenRefreshHandled) {
                const { error } = useNotification();
                error(
                    'Your session has expired. The page will reload to restore your session.',
                    false,
                );

                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }

            // Reset the flag for the next error
            window.csrfTokenRefreshHandled = false;
        }, 2000);
    }
});
