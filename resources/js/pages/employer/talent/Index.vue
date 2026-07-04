<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { MapPin, Search, UserX } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { reactive } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import SkillTags from '@/components/SkillTags.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/employer/talent';
import { countryLabel } from '@/types/kerjago';
import type { CountryCode, Paginated, TalentProfile } from '@/types/kerjago';

const props = defineProps<{
    profiles: Paginated<TalentProfile>;
    filters: {
        q?: string | null;
        country?: string | null;
        city?: string | null;
        experience_min?: number | null;
    };
    countries: CountryCode[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Talent search', href: index() },
        ],
    },
});

const form = reactive({
    q: props.filters.q ?? '',
    country: props.filters.country ?? '',
    city: props.filters.city ?? '',
    experience_min: props.filters.experience_min ?? '',
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
    form.city = '';
    form.experience_min = '';
}
</script>

<template>
    <Head title="Talent search" />

    <div class="grid gap-6 p-4">
        <Heading
            title="Talent search"
            description="Find candidates by name, title, or skills across Southeast Asia."
        />

        <div class="grid gap-3">
            <div class="relative">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="form.q"
                    type="search"
                    placeholder="Search by name, title, or skill…"
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

                <Input
                    v-model="form.city"
                    type="text"
                    placeholder="City"
                    class="w-36"
                />

                <Input
                    v-model.number="form.experience_min"
                    type="number"
                    min="0"
                    placeholder="Min years exp."
                    class="w-36"
                />

                <Button
                    v-if="
                        form.q ||
                        form.country ||
                        form.city ||
                        form.experience_min !== ''
                    "
                    variant="ghost"
                    size="sm"
                    @click="clearFilters"
                >
                    Clear filters
                </Button>
            </div>
        </div>

        <EmptyState
            v-if="profiles.data.length === 0"
            :icon="UserX"
            title="No candidates found"
            description="Try broadening your search or removing filters."
        />

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="profile in profiles.data"
                :key="profile.id"
                :href="show(profile.id)"
                class="group"
            >
                <Card class="h-full transition-shadow group-hover:shadow-md">
                    <CardHeader>
                        <CardTitle class="text-base group-hover:text-primary">
                            {{ profile.full_name }}
                        </CardTitle>
                        <p class="text-sm text-muted-foreground">
                            {{ profile.current_title }} ·
                            {{ profile.experience_years }} yrs
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
                        <SkillTags :skills="profile.skills" :limit="4" />
                    </CardContent>
                </Card>
            </Link>
        </div>

        <PaginationNav :paginator="profiles" />
    </div>
</template>
