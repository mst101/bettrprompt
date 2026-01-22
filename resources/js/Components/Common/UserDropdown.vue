<script setup lang="ts">
import Dropdown from '@/Components/Base/Dropdown.vue';
import DropdownLink from '@/Components/Base/DropdownLink.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    showDashboardLink?: boolean;
}

withDefaults(defineProps<Props>(), {
    showDashboardLink: false,
});

const page = usePage<{
    auth?: { user?: { name: string; isAdmin: boolean } };
}>();

const { countryRoute } = useCountryRoute();
const isAuthenticated = computed(() => !!page.props.auth?.user);
const isAdmin = computed(() => page.props.auth?.user?.isAdmin ?? false);
</script>

<template>
    <div v-if="isAuthenticated" class="relative">
        <Dropdown align="right" width="48">
            <template #trigger>
                <span class="inline-flex rounded-md">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-3 text-sm leading-4 font-medium text-indigo-700 transition duration-150 ease-in-out hover:bg-indigo-50 hover:text-indigo-800 focus:text-indigo-800 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        :aria-label="$t('common.aria.userMenu')"
                    >
                        {{ $page.props.auth!.user!.name }}

                        <DynamicIcon
                            name="chevron-down"
                            class="ms-2 -me-0.5 h-4 w-4"
                        />
                    </button>
                </span>
            </template>

            <template #content>
                <DropdownLink :href="countryRoute('profile.edit')">
                    {{ $t('common.nav.profile') }}
                </DropdownLink>
                <DropdownLink
                    v-if="isAdmin && showDashboardLink"
                    :href="countryRoute('workflows.index')"
                >
                    {{ $t('navigation.workflows') }}
                </DropdownLink>
                <DropdownLink
                    :href="countryRoute('logout')"
                    method="post"
                    as="button"
                >
                    {{ $t('common.nav.logout') }}
                </DropdownLink>
            </template>
        </Dropdown>
    </div>
</template>
