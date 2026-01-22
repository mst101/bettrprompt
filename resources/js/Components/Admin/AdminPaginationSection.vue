<script setup lang="ts">
import LinkButton from '@/Components/Base/LinkButton.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

interface PaginationMeta {
    currentPage: number;
    lastPage: number;
    from: number | null;
    to: number | null;
    perPage: number;
    total: number;
    nextPageUrl?: string | null;
    prevPageUrl?: string | null;
}

interface Props {
    meta: PaginationMeta;
    queryStringParams?: Record<string, unknown>;
}

const props = withDefaults(defineProps<Props>(), {
    queryStringParams: () => ({}),
});

const { t } = useI18n({ useScope: 'global' });
const perPageInput = ref(props.meta.perPage);

const changePerPage = () => {
    const newPerPage = Math.max(
        1,
        Math.min(100, parseInt(perPageInput.value.toString())),
    );
    if (newPerPage !== props.meta.perPage) {
        router.get(window.location.pathname, {
            ...props.queryStringParams,
            per_page: newPerPage,
        });
    }
};
</script>

<template>
    <div
        v-if="meta.lastPage > 1"
        class="border-t border-indigo-200 bg-white px-4 py-3 sm:px-6"
    >
        <!-- Mobile Layout -->
        <div class="sm:hidden">
            <!-- Previous/Next Navigation (mobile) -->
            <div class="mb-4 grid grid-cols-3 items-center gap-2">
                <div class="flex justify-start">
                    <LinkButton
                        v-if="meta.prevPageUrl"
                        :href="meta.prevPageUrl"
                        size="sm"
                    >
                        {{ t('admin.pagination.previous') }}
                    </LinkButton>
                </div>

                <p
                    v-if="meta.lastPage > 1"
                    class="text-center text-sm text-indigo-700"
                >
                    {{
                        t('admin.pagination.pageOf', {
                            current: meta.currentPage,
                            total: meta.lastPage,
                        })
                    }}
                </p>

                <div class="flex justify-end">
                    <LinkButton
                        v-if="meta.nextPageUrl"
                        :href="meta.nextPageUrl"
                        size="sm"
                    >
                        {{ t('admin.pagination.next') }}
                    </LinkButton>
                </div>
            </div>

            <!-- Results info and per-page selector -->
            <div class="space-y-3 text-center text-sm text-indigo-700">
                <i18n-t
                    v-if="meta.from && meta.to"
                    keypath="admin.pagination.resultsSummary"
                    scope="global"
                    tag="p"
                >
                    <span class="font-medium">{{ meta.from }}</span>
                    <span class="font-medium">{{ meta.to }}</span>
                    <span class="font-medium">{{ meta.total }}</span>
                </i18n-t>

                <div class="flex items-center justify-center gap-2">
                    <label
                        for="per-page-mobile"
                        class="text-sm text-indigo-700"
                    >
                        {{ t('admin.pagination.show') }}
                    </label>
                    <input
                        id="per-page-mobile"
                        v-model="perPageInput"
                        type="number"
                        min="1"
                        max="100"
                        class="w-16 rounded-md border-indigo-100 bg-white px-2 py-1 pl-4 text-center text-sm text-black focus:border-indigo-500 focus:ring-indigo-500"
                        @blur="changePerPage"
                        @keydown.enter="changePerPage"
                    />
                    <span class="text-sm text-indigo-700">
                        {{ t('admin.pagination.perPage') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Desktop Layout -->
        <div class="hidden sm:flex sm:items-center sm:justify-between">
            <!-- Results info -->
            <div>
                <i18n-t
                    v-if="meta.from && meta.to"
                    keypath="admin.pagination.resultsSummary"
                    scope="global"
                    tag="p"
                    class="text-sm text-indigo-700"
                >
                    <span class="font-medium">{{ meta.from }}</span>
                    <span class="font-medium">{{ meta.to }}</span>
                    <span class="font-medium">{{ meta.total }}</span>
                </i18n-t>
            </div>

            <!-- Per-page selector (centered) -->
            <div class="flex items-center gap-2">
                <label for="per-page-desktop" class="text-sm text-indigo-700">
                    {{ t('admin.pagination.show') }}
                </label>
                <input
                    id="per-page-desktop"
                    v-model="perPageInput"
                    type="number"
                    min="1"
                    max="100"
                    class="w-16 rounded-md border-indigo-100 bg-white py-1 pl-2 text-center text-sm text-black focus:border-indigo-500 focus:ring-indigo-500"
                    @blur="changePerPage"
                    @keydown.enter="changePerPage"
                />
                <span class="text-sm text-indigo-700">
                    {{ t('admin.pagination.perPage') }}
                </span>
            </div>

            <!-- Navigation -->
            <nav
                v-if="meta.lastPage > 1"
                class="isolate inline-flex -space-x-px rounded-md shadow-xs"
            >
                <LinkButton
                    v-if="meta.prevPageUrl"
                    class="text-indigo-700"
                    :href="meta.prevPageUrl"
                    variant="rounded-left"
                >
                    {{ t('admin.pagination.previous') }}
                </LinkButton>
                <span
                    class="relative inline-flex items-center border border-indigo-100 bg-white px-4 py-2 text-sm font-medium text-indigo-700"
                >
                    {{
                        t('admin.pagination.pageOf', {
                            current: meta.currentPage,
                            total: meta.lastPage,
                        })
                    }}
                </span>
                <LinkButton
                    v-if="meta.nextPageUrl"
                    class="text-indigo-700"
                    :href="meta.nextPageUrl"
                    variant="rounded-right"
                >
                    {{ t('admin.pagination.next') }}
                </LinkButton>
            </nav>
        </div>
    </div>
</template>
