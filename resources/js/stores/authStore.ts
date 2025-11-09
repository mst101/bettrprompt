import type { UserResource } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { defineStore } from 'pinia';
import { computed, ref, watch } from 'vue';

export const useAuthStore = defineStore('auth', () => {
    const user = ref<UserResource | null>(null);
    const isAuthenticated = computed(() => !!user.value);

    // Initialise from Inertia page props
    function initialiseFromPage() {
        const page = usePage();
        const pageUser = page.props?.auth?.user as UserResource | undefined;

        if (pageUser) {
            user.value = pageUser;
            console.log(
                'Auth store initialised with user:',
                pageUser.id,
                pageUser.email || pageUser.phone,
            );
        } else {
            user.value = null;
            console.log('Auth store initialised - no user authenticated');
        }
    }

    // Update user data
    function setUser(userData: UserResource | null) {
        user.value = userData;
        console.log(
            'Auth store user updated:',
            userData
                ? `${userData.id} (${userData.email || userData.phone})`
                : 'logged out',
        );
    }

    // Clear user data on logout
    function clearUser() {
        user.value = null;
        console.log('Auth store user cleared');
    }

    // Watch for changes in Inertia page props
    function watchPageProps() {
        const page = usePage();

        watch(
            () => page.props?.auth?.user,
            (newUser) => {
                if (newUser && (!user.value || user.value.id !== newUser.id)) {
                    setUser(newUser as UserResource);
                } else if (!newUser && user.value) {
                    clearUser();
                }
            },
            { immediate: true, deep: true },
        );
    }

    return {
        user: computed(() => user.value),
        isAuthenticated,
        initialiseFromPage,
        setUser,
        clearUser,
        watchPageProps,
    };
});
