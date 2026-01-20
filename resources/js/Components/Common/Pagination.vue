<script setup lang="ts">
import LinkButton from '@/Components/Base/LinkButton.vue';
import { useI18n } from 'vue-i18n';

interface PaginationMeta {
    currentPage: number;
    lastPage: number;
    from: number | null;
    to: number | null;
    perPage: number;
    path: string;
    total: number;
    hasMorePages: boolean;
    nextPageUrl: string | null;
    prevPageUrl: string | null;
}

interface Props {
    meta: PaginationMeta;
    showPageInfo?: boolean;
}

withDefaults(defineProps<Props>(), {
    showPageInfo: true,
});

const { t } = useI18n({ useScope: 'global' });
</script>

<template>
    <nav
        v-if="meta.lastPage > 1"
        class="isolate inline-flex -space-x-px rounded-md shadow-xs"
        aria-label="Pagination"
    >
        <LinkButton
            v-if="meta.prevPageUrl"
            :href="meta.prevPageUrl"
            variant="rounded-left"
            data-testid="pagination-prev"
        >
            {{ t('common.pagination.previous') }}
        </LinkButton>
        <span
            v-if="showPageInfo"
            class="relative inline-flex items-center border border-indigo-100 bg-white px-4 py-2 text-sm font-medium text-indigo-700"
        >
            {{
                t('common.pagination.pageOf', {
                    current: meta.currentPage,
                    total: meta.lastPage,
                })
            }}
        </span>
        <LinkButton
            v-if="meta.nextPageUrl"
            :href="meta.nextPageUrl"
            variant="rounded-right"
            data-testid="pagination-next"
        >
            {{ t('common.pagination.next') }}
        </LinkButton>
    </nav>
</template>
