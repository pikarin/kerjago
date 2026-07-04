<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Search, SearchX } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { reactive } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
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
import { countryLabel } from '@/types/kerjago';
import type {
    CountryCode,
    CurrencyCode,
    JobSummary,
    Paginated,
} from '@/types/kerjago';

const props = defineProps<{
    jobs: Paginated<JobSummary>;
    filters: {
        q?: string | null;
        country?: string | null;
        currency?: string | null;
        salary_min?: number | null;
        salary_max?: number | null;
    };
    countries: CountryCode[];
    currencies: CurrencyCode[];
}>();

const form = reactive({
    q: props.filters.q ?? '',
    country: props.filters.country ?? '',
    currency: props.filters.currency ?? '',
    salary_min: props.filters.salary_min ?? '',
    salary_max: props.filters.salary_max ?? '',
});

// Debounced search per the PRD: wait 300ms after the user stops typing.
watchDebounced(
    form,
    () => {
        router.get(
            index().url,
            Object.fromEntries(
                Object.entries(form).filter(
                    ([, value]) => value !== '' && value !== null,
                ),
            ),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    },
    { debounce: 300, deep: true },
);

function clearFilters(): void {
    form.q = '';
    form.country = '';
    form.currency = '';
    form.salary_min = '';
    form.salary_max = '';
}

const hasFilters = (): boolean =>
    form.q !== '' ||
    form.country !== '' ||
    form.currency !== '' ||
    form.salary_min !== '' ||
    form.salary_max !== '';
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

        <div class="grid gap-3">
            <div class="relative">
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

            <div class="flex flex-wrap items-center gap-2">
                <Select v-model="form.country">
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="Country" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="country in countries"
                            :key="country"
                            :value="country"
                        >
                            {{ countryLabel(country) }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="form.currency">
                    <SelectTrigger class="w-32">
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

                <Input
                    v-model.number="form.salary_min"
                    type="number"
                    min="0"
                    placeholder="Min salary"
                    class="w-32"
                />
                <Input
                    v-model.number="form.salary_max"
                    type="number"
                    min="0"
                    placeholder="Max salary"
                    class="w-32"
                />

                <Button
                    v-if="hasFilters()"
                    variant="ghost"
                    size="sm"
                    @click="clearFilters"
                >
                    Clear filters
                </Button>
            </div>
        </div>

        <div
            v-if="jobs.data.length > 0"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <JobCard v-for="job in jobs.data" :key="job.id" :job="job" />
        </div>

        <EmptyState
            v-else
            :icon="SearchX"
            title="No jobs found"
            description="Try adjusting your keywords or removing some filters."
        />

        <PaginationNav :paginator="jobs" />
    </div>
</template>
