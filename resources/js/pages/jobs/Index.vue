<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Search, SearchX } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, reactive, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import FacetGroup from '@/components/FacetGroup.vue';
import JobCard from '@/components/JobCard.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index } from '@/routes/jobs';
import type {
    CurrencyCode,
    FacetOption,
    Facets,
    JobSummary,
    Paginated,
} from '@/types/kerjago';

const props = defineProps<{
    jobs: Paginated<JobSummary>;
    filters: {
        q?: string | null;
        country?: string[] | null;
        employment_type?: string[] | null;
        work_arrangement?: string[] | null;
        experience_level?: string[] | null;
        education_level?: string[] | null;
        skills?: string[] | null;
        currency?: string | null;
        salary_min?: number | null;
        salary_max?: number | null;
        sort?: string | null;
    };
    facets: Facets;
    facetsAvailable: boolean;
    facetOptions: {
        country: FacetOption[];
        employment_type: FacetOption[];
        work_arrangement: FacetOption[];
        experience_level: FacetOption[];
        education_level: FacetOption[];
    };
    currencies: CurrencyCode[];
}>();

const form = reactive({
    q: props.filters.q ?? '',
    country: props.filters.country ?? [],
    employment_type: props.filters.employment_type ?? [],
    work_arrangement: props.filters.work_arrangement ?? [],
    experience_level: props.filters.experience_level ?? [],
    education_level: props.filters.education_level ?? [],
    skills: props.filters.skills ?? [],
    currency: props.filters.currency ?? '',
    salary_min: props.filters.salary_min ?? '',
    salary_max: props.filters.salary_max ?? '',
    sort: props.filters.sort ?? (props.filters.q ? 'relevance' : 'newest'),
});

// Relevance ranking needs a query: force newest when browsing without one,
// and switch to relevance when a search starts (only on the empty → typed
// transition, so a deliberate newest choice survives query edits).
watch(
    () => form.q,
    (q, previous) => {
        if (q === '' && form.sort === 'relevance') {
            form.sort = 'newest';
        } else if (q !== '' && previous === '' && form.sort === 'newest') {
            form.sort = 'relevance';
        }
    },
);

// Skills have no fixed option list — offer whatever the engine surfaces.
const skillOptions = computed<FacetOption[]>(() => {
    const fromCounts = (props.facets.skills ?? []).map((c) => ({
        value: c.value,
        label: c.value,
    }));
    const missing = form.skills
        .filter((s) => !fromCounts.some((o) => o.value === s))
        .map((s) => ({ value: s, label: s }));

    return [...fromCounts, ...missing];
});

// Debounced search per the PRD: wait 300ms after the user stops typing.
watchDebounced(
    form,
    () => {
        router.get(
            index().url,
            Object.fromEntries(
                Object.entries(form).filter(
                    ([key, value]) =>
                        value !== '' &&
                        value !== null &&
                        !(Array.isArray(value) && value.length === 0) &&
                        !(
                            key === 'sort' &&
                            value === 'newest' &&
                            form.q === ''
                        ),
                ),
            ),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    },
    { debounce: 300, deep: true },
);

function clearFilters(): void {
    form.q = '';
    form.country = [];
    form.employment_type = [];
    form.work_arrangement = [];
    form.experience_level = [];
    form.education_level = [];
    form.skills = [];
    form.currency = '';
    form.salary_min = '';
    form.salary_max = '';
    form.sort = 'newest';
}

const hasFilters = computed(
    () =>
        form.q !== '' ||
        form.country.length > 0 ||
        form.employment_type.length > 0 ||
        form.work_arrangement.length > 0 ||
        form.experience_level.length > 0 ||
        form.education_level.length > 0 ||
        form.skills.length > 0 ||
        form.currency !== '' ||
        form.salary_min !== '' ||
        form.salary_max !== '',
);
</script>

<template>
    <Head title="Find jobs" />

    <div class="grid gap-6">
        <div class="grid gap-1">
            <h1 class="text-2xl font-semibold tracking-tight">
                Find your next role
            </h1>
            <p class="text-muted-foreground">
                Search {{ jobs.total }} open positions across Southeast Asia.
            </p>
        </div>

        <div class="lg:grid lg:grid-cols-[16rem_1fr] lg:items-start lg:gap-8">
            <aside class="mb-6 grid gap-6 lg:mb-0">
                <FacetGroup
                    v-model="form.work_arrangement"
                    title="Work arrangement"
                    :options="facetOptions.work_arrangement"
                    :counts="
                        facetsAvailable
                            ? (facets.work_arrangement ?? [])
                            : undefined
                    "
                />
                <FacetGroup
                    v-model="form.employment_type"
                    title="Employment type"
                    :options="facetOptions.employment_type"
                    :counts="
                        facetsAvailable
                            ? (facets.employment_type ?? [])
                            : undefined
                    "
                />
                <FacetGroup
                    v-model="form.experience_level"
                    title="Experience level"
                    :options="facetOptions.experience_level"
                    :counts="
                        facetsAvailable
                            ? (facets.experience_level ?? [])
                            : undefined
                    "
                />
                <FacetGroup
                    v-model="form.education_level"
                    title="Education"
                    :options="facetOptions.education_level"
                    :counts="
                        facetsAvailable
                            ? (facets.education_level ?? [])
                            : undefined
                    "
                />
                <FacetGroup
                    v-model="form.country"
                    title="Country"
                    :options="facetOptions.country"
                    :counts="
                        facetsAvailable
                            ? (facets.location_country ?? [])
                            : undefined
                    "
                />
                <FacetGroup
                    v-if="facetsAvailable && skillOptions.length > 0"
                    v-model="form.skills"
                    title="Skills"
                    :options="skillOptions"
                    :counts="facets.skills ?? []"
                />

                <div class="grid gap-2">
                    <span class="text-sm font-medium">Salary</span>
                    <Select v-model="form.currency">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Currency" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="currency in currencies"
                                :key="currency"
                                :value="currency"
                            >
                                {{ currency }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <div class="flex gap-2">
                        <Input
                            v-model.number="form.salary_min"
                            type="number"
                            min="0"
                            placeholder="Min"
                        />
                        <Input
                            v-model.number="form.salary_max"
                            type="number"
                            min="0"
                            placeholder="Max"
                        />
                    </div>
                </div>

                <Button
                    v-if="hasFilters"
                    variant="ghost"
                    size="sm"
                    class="justify-self-start"
                    @click="clearFilters"
                >
                    Clear filters
                </Button>
            </aside>

            <div class="grid gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative min-w-64 flex-1">
                        <Search
                            class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="form.q"
                            type="search"
                            placeholder="Search by title, keyword, or city…"
                            class="h-11 pl-9"
                        />
                    </div>

                    <Select v-model="form.sort">
                        <SelectTrigger class="w-36">
                            <SelectValue placeholder="Sort" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-if="form.q !== ''" value="relevance">
                                Relevance
                            </SelectItem>
                            <SelectItem value="newest">Newest</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div
                    v-if="jobs.data.length > 0"
                    class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                >
                    <JobCard
                        v-for="job in jobs.data"
                        :key="job.id"
                        :job="job"
                    />
                </div>

                <EmptyState
                    v-else
                    :icon="SearchX"
                    title="No jobs found"
                    description="Try adjusting your keywords or removing some filters."
                />

                <PaginationNav :paginator="jobs" />
            </div>
        </div>
    </div>
</template>
