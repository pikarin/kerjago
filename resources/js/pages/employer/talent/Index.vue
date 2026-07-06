<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CalendarClock,
    ChevronsUpDown,
    MapPin,
    Search,
    UserX,
} from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, reactive, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import FacetGroup from '@/components/FacetGroup.vue';
import Heading from '@/components/Heading.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import SkillTags from '@/components/SkillTags.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/employer/talent';
import { countryLabel } from '@/types/kerjago';
import type {
    FacetOption,
    Facets,
    Paginated,
    TalentProfile,
} from '@/types/kerjago';

const props = defineProps<{
    profiles: Paginated<TalentProfile>;
    filters: {
        q?: string | null;
        preferred_job_title?: string[] | null;
        skills?: string[] | null;
        experience_band?: string[] | null;
        availability?: string[] | null;
        country?: string[] | null;
        city?: string[] | null;
        preferred_country?: string[] | null;
        preferred_city?: string[] | null;
        languages?: string[] | null;
        education_level?: string[] | null;
        gender?: string[] | null;
        experience_min?: number | null;
    };
    facets: Facets;
    facetsAvailable: boolean;
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

const AVAILABILITY_LABELS: Record<string, string> = {
    immediately: 'Available now',
    two_weeks: 'In 2 weeks',
    one_month: 'In 1 month',
    two_months_plus: 'In 2+ months',
};

const form = reactive({
    q: props.filters.q ?? '',
    preferred_job_title: props.filters.preferred_job_title ?? [],
    skills: props.filters.skills ?? [],
    experience_band: props.filters.experience_band ?? [],
    availability: props.filters.availability ?? [],
    country: props.filters.country ?? [],
    city: props.filters.city ?? [],
    preferred_country: props.filters.preferred_country ?? [],
    preferred_city: props.filters.preferred_city ?? [],
    languages: props.filters.languages ?? [],
    education_level: props.filters.education_level ?? [],
    gender: props.filters.gender ?? [],
    experience_min: props.filters.experience_min ?? '',
});

// The secondary facets start open when one of them is already active.
const moreFiltersOpen = ref(
    form.preferred_country.length > 0 ||
        form.preferred_city.length > 0 ||
        form.languages.length > 0 ||
        form.education_level.length > 0 ||
        form.gender.length > 0,
);

// These facets have no fixed option list — offer whatever the engine
// surfaces, keeping currently-selected values visible even at zero count.
function engineOptions(facet: string, selected: string[]): FacetOption[] {
    const fromCounts = (props.facets[facet] ?? []).map((c) => ({
        value: c.value,
        label: c.value,
    }));
    const missing = selected
        .filter((s) => !fromCounts.some((o) => o.value === s))
        .map((s) => ({ value: s, label: s }));

    return [...fromCounts, ...missing];
}

const titleOptions = computed<FacetOption[]>(() =>
    engineOptions('preferred_job_title', form.preferred_job_title),
);
const skillOptions = computed<FacetOption[]>(() =>
    engineOptions('skills', form.skills),
);
const cityOptions = computed<FacetOption[]>(() =>
    engineOptions('city', form.city),
);
const preferredCityOptions = computed<FacetOption[]>(() =>
    engineOptions('preferred_city', form.preferred_city),
);

// Debounced search per the PRD: wait 300ms after the user stops typing.
watchDebounced(
    form,
    () => {
        router.get(
            index().url,
            Object.fromEntries(
                Object.entries(form).filter(
                    ([, value]) =>
                        value !== '' &&
                        value !== null &&
                        !(Array.isArray(value) && value.length === 0),
                ),
            ),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    },
    { debounce: 300, deep: true },
);

function clearFilters(): void {
    form.q = '';
    form.preferred_job_title = [];
    form.skills = [];
    form.experience_band = [];
    form.availability = [];
    form.country = [];
    form.city = [];
    form.preferred_country = [];
    form.preferred_city = [];
    form.languages = [];
    form.education_level = [];
    form.gender = [];
    form.experience_min = '';
}

const hasFilters = computed(
    () =>
        form.q !== '' ||
        form.experience_min !== '' ||
        [
            form.preferred_job_title,
            form.skills,
            form.experience_band,
            form.availability,
            form.country,
            form.city,
            form.preferred_country,
            form.preferred_city,
            form.languages,
            form.education_level,
            form.gender,
        ].some((values) => values.length > 0),
);
</script>

<template>
    <Head title="Talent search" />

    <div class="grid gap-6 p-4">
        <Heading
            title="Talent search"
            description="Find candidates by role, experience, or skills across Southeast Asia."
        />

        <div class="lg:grid lg:grid-cols-[16rem_1fr] lg:items-start lg:gap-8">
            <aside class="mb-6 grid gap-6 lg:mb-0">
                <FacetGroup
                    v-if="facetsAvailable && titleOptions.length > 0"
                    v-model="form.preferred_job_title"
                    title="Preferred job title"
                    :options="titleOptions"
                    :counts="facets.preferred_job_title ?? []"
                />
                <FacetGroup
                    v-if="facetsAvailable && skillOptions.length > 0"
                    v-model="form.skills"
                    title="Skills"
                    :options="skillOptions"
                    :counts="facets.skills ?? []"
                />
                <FacetGroup
                    v-model="form.experience_band"
                    title="Experience"
                    :options="facetOptions.experience_band"
                    :counts="
                        facetsAvailable
                            ? (facets.experience_band ?? [])
                            : undefined
                    "
                />
                <FacetGroup
                    v-model="form.availability"
                    title="Availability"
                    :options="facetOptions.availability"
                    :counts="
                        facetsAvailable
                            ? (facets.availability ?? [])
                            : undefined
                    "
                />
                <FacetGroup
                    v-model="form.country"
                    title="Country"
                    :options="facetOptions.country"
                    :counts="
                        facetsAvailable ? (facets.country ?? []) : undefined
                    "
                />
                <FacetGroup
                    v-if="facetsAvailable && cityOptions.length > 0"
                    v-model="form.city"
                    title="City"
                    :options="cityOptions"
                    :counts="facets.city ?? []"
                />

                <div class="grid gap-2">
                    <span class="text-sm font-medium">Minimum experience</span>
                    <Input
                        v-model.number="form.experience_min"
                        type="number"
                        min="0"
                        placeholder="Min years exp."
                    />
                </div>

                <Collapsible v-model:open="moreFiltersOpen" class="grid gap-6">
                    <CollapsibleTrigger as-child>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="justify-self-start"
                        >
                            <ChevronsUpDown class="size-4" />
                            More filters
                        </Button>
                    </CollapsibleTrigger>
                    <CollapsibleContent class="grid gap-6">
                        <FacetGroup
                            v-model="form.preferred_country"
                            title="Preferred country"
                            :options="facetOptions.preferred_country"
                            :counts="
                                facetsAvailable
                                    ? (facets.preferred_country ?? [])
                                    : undefined
                            "
                        />
                        <FacetGroup
                            v-if="
                                facetsAvailable &&
                                preferredCityOptions.length > 0
                            "
                            v-model="form.preferred_city"
                            title="Preferred city"
                            :options="preferredCityOptions"
                            :counts="facets.preferred_city ?? []"
                        />
                        <FacetGroup
                            v-model="form.languages"
                            title="Languages"
                            :options="facetOptions.languages"
                            :counts="
                                facetsAvailable
                                    ? (facets.languages ?? [])
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
                            v-model="form.gender"
                            title="Gender"
                            :options="facetOptions.gender"
                            :counts="
                                facetsAvailable
                                    ? (facets.gender ?? [])
                                    : undefined
                            "
                        />
                    </CollapsibleContent>
                </Collapsible>

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
                <div class="relative">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="form.q"
                        type="search"
                        placeholder="Search by role, skill, or location…"
                        class="h-11 pl-9"
                    />
                </div>

                <div
                    v-if="profiles.data.length > 0"
                    class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                >
                    <Link
                        v-for="profile in profiles.data"
                        :key="profile.id"
                        :href="show(profile.id)"
                        class="group"
                    >
                        <Card
                            class="h-full transition-shadow group-hover:shadow-md"
                        >
                            <CardHeader>
                                <CardTitle
                                    class="text-base group-hover:text-primary"
                                >
                                    {{ profile.full_name }}
                                </CardTitle>
                                <p class="text-sm text-muted-foreground">
                                    {{
                                        profile.preferred_job_title ??
                                        profile.current_title
                                    }}
                                    · {{ profile.experience_years }} yrs
                                </p>
                            </CardHeader>
                            <CardContent class="grid gap-3">
                                <p
                                    class="flex items-center gap-1.5 text-sm text-muted-foreground"
                                >
                                    <MapPin class="size-3.5" />
                                    {{ profile.city }},
                                    {{ countryLabel(profile.country) }}
                                </p>
                                <SkillTags
                                    :skills="profile.skills"
                                    :limit="4"
                                />
                                <Badge
                                    v-if="profile.availability"
                                    variant="secondary"
                                    class="justify-self-start"
                                >
                                    <CalendarClock class="size-3" />
                                    {{
                                        AVAILABILITY_LABELS[
                                            profile.availability
                                        ]
                                    }}
                                </Badge>
                            </CardContent>
                        </Card>
                    </Link>
                </div>

                <EmptyState
                    v-else
                    :icon="UserX"
                    title="No candidates found"
                    description="Try broadening your search or removing filters."
                />

                <PaginationNav :paginator="profiles" />
            </div>
        </div>
    </div>
</template>
