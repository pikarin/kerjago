<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Paginated } from '@/types/kerjago';

defineProps<{
    paginator: Paginated<unknown>;
}>();

/**
 * Paired with preserve-scroll on the links: Inertia's default scroll reset
 * would snap to the top before the new page has rendered, so the visit keeps
 * the scroll position and this glides up once the swap has happened.
 */
function scrollToTop(): void {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function plainLabel(label: string): string {
    return label
        .replace('&laquo;', '«')
        .replace('&raquo;', '»')
        .replace('&hellip;', '…');
}
</script>

<template>
    <nav
        v-if="paginator.last_page > 1"
        class="flex items-center justify-between gap-4"
        aria-label="Pagination"
    >
        <p class="text-sm text-muted-foreground">
            Showing {{ paginator.from ?? 0 }}–{{ paginator.to ?? 0 }} of
            {{ paginator.total }}
        </p>
        <div class="flex flex-wrap items-center gap-1">
            <template v-for="(link, index) in paginator.links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    @success="scrollToTop"
                    class="flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm transition-colors"
                    :class="
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-input hover:bg-muted'
                    "
                >
                    {{ plainLabel(link.label) }}
                </Link>
                <span
                    v-else
                    class="flex h-8 min-w-8 items-center justify-center px-2 text-sm text-muted-foreground"
                >
                    {{ plainLabel(link.label) }}
                </span>
            </template>
        </div>
    </nav>
</template>
