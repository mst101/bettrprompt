import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { createApp, DefineComponent, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { getCookie, getCsrfToken } from './utils/cookies';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pinia = createPinia();

// Visitor tracking with localStorage backup
function setupVisitorTracking() {
    const visitorId = getCookie('visitor_id');

    if (visitorId) {
        // Cookie exists → backup to localStorage
        localStorage.setItem('visitor_id_backup', visitorId);
    } else {
        // Cookie deleted → check localStorage
        const backupId = localStorage.getItem('visitor_id_backup');

        if (backupId) {
            // Send to server to recreate cookie
            const csrfToken = getCsrfToken();

            if (csrfToken) {
                fetch('/api/restore-visitor', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ visitor_id: backupId }),
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.restored) {
                            console.log('Visitor cookie restored from backup');
                        }
                    })
                    .catch((error) => {
                        console.error(
                            'Failed to restore visitor cookie:',
                            error,
                        );
                    });
            }
        }
    }
}

// Run visitor tracking setup
setupVisitorTracking();

// Fullstory visitor identification
function identifyVisitorInFullstory(auth: any) {
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
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue)
            .mount(el);

        // Identify visitor in Fullstory on initial load
        identifyVisitorInFullstory(props.initialPage.props.auth);

        return app;
    },
    progress: {
        color: '#4B5563',
    },
});

// Global error handler for 419 Page Expired errors
router.on('error', (event) => {
    const response = event.detail?.response;

    // Check if the error is a 419 (Page Expired / CSRF token mismatch)
    if (response?.status === 419) {
        console.warn('Session expired (419 error). Reloading page...');

        // Show a user-friendly message before reloading
        alert(
            'Your session has expired. The page will reload to restore your session.',
        );

        // Reload the page to get a fresh CSRF token
        window.location.reload();
    }
});
