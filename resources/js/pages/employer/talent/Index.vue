<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Search, SlidersHorizontal, UserX } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, reactive, ref } from 'vue';
import CapabilityWall from '@/components/CapabilityWall.vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import TalentCard from '@/components/TalentCard.vue';
import TalentFilterPanel from '@/components/TalentFilterPanel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { dashboard } from '@/routes';
import { index } from '@/routes/employer/talent';
import type { CapabilityDecision } from '@/types/capabilities';
import type {
    FacetOption,
    Facets,
    Paginated,
    TalentFilterForm,
    TalentSummary,
} from '@/types/kerjago';

const props = defineProps<{
    profiles: Paginated<TalentSummary>;
    filters: Partial<TalentFilterForm> & { q?: string | null };
    facets: Facets;
    facetsAvailable: boolean;
    /**
     * Profile id => card fields the engine matched the keyword on. Field
     * names only; the card tints those fields whole.
     */
    highlights: Record<string, string[]>;
    browseInFull: CapabilityDecision;
    /**
     * Whether candidates beyond this page exist and are being withheld. Only
     * the server can answer it: the page carries no total to compare against.
     */
    resultsWithheld: boolean;
    facetOptions: {
        experience_band: FacetOption[];
        availability: FacetOption[];
        country: FacetOption[];
        preferred_country: FacetOption[];
        languages: FacetOption[];
        education_level: FacetOption[];
        gender: FacetOption[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Talent search', href: index() },
        ],
    },
});

// The keyword only travels on an explicit submit (button or Enter)…
const keyword = ref(props.filters.q ?? '');

// …while the sidebar filters apply themselves as they change.
const form = reactive<TalentFilterForm>({
    experience_band: props.filters.experience_band ?? [],
    availability: props.filters.availability ?? [],
    country: props.filters.country ?? [],
    preferred_country: props.filters.preferred_country ?? [],
    languages: props.filters.languages ?? [],
    education_level: props.filters.education_level ?? [],
    gender: props.filters.gender ?? [],
    experience_min: props.filters.experience_min ?? '',
});

function search(): void {
    const params = Object.fromEntries(
        Object.entries({ q: keyword.value.trim(), ...form }).filter(
            ([, value]) =>
                value !== '' &&
                value !== null &&
                !(Array.isArray(value) && value.length === 0),
        ),
    );

    router.get(index().url, params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

// Debounced so ticking three checkboxes in a row costs one request, not three.
watchDebounced(form, search, { debounce: 300, deep: true });

function clearFilters(): void {
    form.experience_band = [];
    form.availability = [];
    form.country = [];
    form.preferred_country = [];
    form.languages = [];
    form.education_level = [];
    form.gender = [];
    form.experience_min = '';
}

const activeFilterCount = computed(
    () =>
        [
            form.experience_band,
            form.availability,
            form.country,
            form.preferred_country,
            form.languages,
            form.education_level,
            form.gender,
        ].filter((values) => values.length > 0).length +
        (form.experience_min === '' ? 0 : 1),
);

const hasFilters = computed(() => activeFilterCount.value > 0);
</script>

<template>
    <Head title="Talent search" />

    <div class="mx-auto grid w-full max-w-6xl gap-6 p-4">
        <Heading
            title="Talent search"
            description="Find candidates by role, experience, or skills across Southeast Asia."
        />

        <form
            class="flex items-center gap-2 rounded-xl border bg-card p-2 shadow-sm"
            @submit.prevent="search"
        >
            <Search class="ml-3 size-5 shrink-0 text-muted-foreground" />
            <Input
                v-model="keyword"
                type="search"
                placeholder="Try a role, a skill, or what the work involves…"
                class="h-12 border-0 !bg-transparent text-base shadow-none focus-visible:ring-0 dark:!bg-transparent"
            />
            <Button type="submit" size="lg" class="shrink-0">
                <Search class="size-4" />
                Search
            </Button>
        </form>

        <div class="flex items-center justify-between gap-4 lg:hidden">
            <Sheet>
                <SheetTrigger as-child>
                    <Button variant="outline">
                        <SlidersHorizontal class="size-4" />
                        Filters
                        <Badge v-if="hasFilters" variant="secondary">
                            {{ activeFilterCount }}
                        </Badge>
                    </Button>
                </SheetTrigger>
                <SheetContent
                    side="left"
                    class="w-80 overflow-y-auto px-4 pb-8"
                >
                    <SheetHeader class="px-0">
                        <SheetTitle>Filters</SheetTitle>
                    </SheetHeader>
                    <TalentFilterPanel
                        v-model="form"
                        :facets="facets"
                        :facets-available="facetsAvailable"
                        :facet-options="facetOptions"
                        :has-filters="hasFilters"
                        @clear="clearFilters"
                    />
                </SheetContent>
            </Sheet>

            <p
                v-if="browseInFull.allowed"
                class="text-sm text-muted-foreground"
            >
                {{ profiles.total }}
                {{ profiles.total === 1 ? 'candidate' : 'candidates' }} found
            </p>
        </div>

        <div class="lg:grid lg:grid-cols-[16rem_1fr] lg:items-start lg:gap-8">
            <!-- Sticky offset matches the h-16 app header it parks under. -->
            <aside
                class="sticky top-16 hidden max-h-[calc(100svh-4rem)] overflow-y-auto rounded-xl border p-4 lg:block"
            >
                <TalentFilterPanel
                    v-model="form"
                    :facets="facets"
                    :facets-available="facetsAvailable"
                    :facet-options="facetOptions"
                    :has-filters="hasFilters"
                    @clear="clearFilters"
                />
            </aside>

            <div class="grid gap-4">
                <p
                    v-if="browseInFull.allowed"
                    class="hidden text-sm text-muted-foreground lg:block"
                >
                    {{ profiles.total }}
                    {{ profiles.total === 1 ? 'candidate' : 'candidates' }}
                    found
                </p>

                <template v-if="profiles.data.length > 0">
                    <TalentCard
                        v-for="profile in profiles.data"
                        :key="profile.id"
                        :profile="profile"
                        :matched-fields="highlights[profile.id] ?? []"
                        :filters="form"
                    />
                </template>

                <EmptyState
                    v-else
                    :icon="UserX"
                    title="No candidates found"
                    description="Try broadening your search or removing filters."
                />

                <!--
                    Without the full-browse capability the results stop after one
                    page and the wall takes over. No pagination and no total, so
                    the pool's depth cannot be counted off the page — the cards
                    above are the whole of what this account may see.

                    Raised only when the server says something is genuinely
                    being held back. A search that returned everything there was
                    has no next candidate to withhold, and claiming otherwise is
                    a lie the employer can check — which is why this is a
                    server-supplied bit rather than a full page of results
                    guessed at from `data.length`.
                -->
                <CapabilityWall
                    v-if="resultsWithheld"
                    :reason="browseInFull.reason"
                    subject="candidates"
                />

                <PaginationNav
                    v-if="browseInFull.allowed"
                    :paginator="profiles"
                />
            </div>
        </div>
    </div>
</template>
