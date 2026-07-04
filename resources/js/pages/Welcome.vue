<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BriefcaseBusiness,
    Building2,
    Search,
    Sparkles,
    UserSearch,
    Zap,
} from '@lucide/vue';
import { ref } from 'vue';
import JobCard from '@/components/JobCard.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { register } from '@/routes';
import { index as jobsIndex } from '@/routes/jobs';
import type { User } from '@/types';
import { COUNTRY_LABELS  } from '@/types/kerjago';
import type {JobSummary} from '@/types/kerjago';

defineProps<{
    stats: {
        active_jobs: number;
        companies: number;
        candidates: number;
    };
    latestJobs: JobSummary[];
}>();

const page = usePage();
const user = page.props.auth.user as User | null;

const searchQuery = ref('');

const popularSearches = ['Laravel', 'Vue.js', 'Designer', 'Python', 'SEO'];

function search(keyword?: string): void {
    const q = keyword ?? searchQuery.value.trim();

    router.get(jobsIndex().url, q ? { q } : {});
}
</script>

<template>
    <Head title="Find your next job in Southeast Asia" />

    <div class="grid gap-16 pb-8">
        <!-- Hero -->
        <section
            class="relative -mx-4 overflow-hidden bg-gradient-to-b from-primary/10 via-primary/5 to-transparent px-4 pt-16 pb-12 sm:pt-24"
        >
            <div class="mx-auto grid max-w-3xl gap-6 text-center">
                <p
                    class="mx-auto flex w-fit items-center gap-1.5 rounded-full border bg-background px-3 py-1 text-xs font-medium text-muted-foreground"
                >
                    <Sparkles class="size-3.5 text-primary" />
                    Hiring across {{ Object.keys(COUNTRY_LABELS).length }}
                    Southeast Asian markets
                </p>

                <h1
                    class="text-4xl font-bold tracking-tight text-balance sm:text-6xl"
                >
                    Your next job in SEA is
                    <span class="text-primary">one search away</span>
                </h1>

                <p class="mx-auto max-w-xl text-lg text-muted-foreground">
                    KerjaGo connects talent with companies across Indonesia,
                    Singapore, Malaysia, the Philippines, Vietnam, and Thailand.
                    Search, apply, and track — all in one place.
                </p>

                <form
                    class="mx-auto flex w-full max-w-xl items-center gap-2"
                    @submit.prevent="search()"
                >
                    <div class="relative flex-1">
                        <Search
                            class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Job title, skill, or city…"
                            class="h-12 bg-background pl-9 text-base shadow-sm"
                        />
                    </div>
                    <Button type="submit" size="lg" class="h-12">
                        Search jobs
                    </Button>
                </form>

                <div
                    class="flex flex-wrap items-center justify-center gap-2 text-sm"
                >
                    <span class="text-muted-foreground">Popular:</span>
                    <button
                        v-for="keyword in popularSearches"
                        :key="keyword"
                        type="button"
                        class="rounded-full border bg-background px-3 py-1 text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                        @click="search(keyword)"
                    >
                        {{ keyword }}
                    </button>
                </div>
            </div>
        </section>

        <!-- Stats -->
        <section class="grid gap-4 sm:grid-cols-3">
            <div class="flex items-center gap-4 rounded-xl border p-5">
                <div
                    class="flex size-11 items-center justify-center rounded-lg bg-primary/10 text-primary"
                >
                    <BriefcaseBusiness class="size-5" />
                </div>
                <div>
                    <p class="text-2xl font-semibold">
                        {{ stats.active_jobs }}
                    </p>
                    <p class="text-sm text-muted-foreground">Open jobs</p>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-xl border p-5">
                <div
                    class="flex size-11 items-center justify-center rounded-lg bg-primary/10 text-primary"
                >
                    <Building2 class="size-5" />
                </div>
                <div>
                    <p class="text-2xl font-semibold">{{ stats.companies }}</p>
                    <p class="text-sm text-muted-foreground">
                        Companies hiring
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-xl border p-5">
                <div
                    class="flex size-11 items-center justify-center rounded-lg bg-primary/10 text-primary"
                >
                    <UserSearch class="size-5" />
                </div>
                <div>
                    <p class="text-2xl font-semibold">{{ stats.candidates }}</p>
                    <p class="text-sm text-muted-foreground">
                        Candidates on board
                    </p>
                </div>
            </div>
        </section>

        <!-- Latest jobs -->
        <section v-if="latestJobs.length > 0" class="grid gap-6">
            <div class="flex items-end justify-between gap-4">
                <div class="grid gap-1">
                    <h2 class="text-2xl font-semibold tracking-tight">
                        Fresh openings
                    </h2>
                    <p class="text-muted-foreground">
                        The latest roles posted on KerjaGo.
                    </p>
                </div>
                <Button as-child variant="ghost">
                    <Link :href="jobsIndex()">
                        View all jobs <ArrowRight class="size-4" />
                    </Link>
                </Button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <JobCard v-for="job in latestJobs" :key="job.id" :job="job" />
            </div>
        </section>

        <!-- Role CTAs -->
        <section v-if="!user" class="grid gap-4 sm:grid-cols-2">
            <Card class="bg-primary text-primary-foreground">
                <CardContent class="grid gap-3 p-6">
                    <UserSearch class="size-6" />
                    <h3 class="text-xl font-semibold">Looking for work?</h3>
                    <p class="text-sm opacity-90">
                        Build your profile once, apply in one click, and track
                        every application from submitted to shortlisted.
                    </p>
                    <Button as-child variant="secondary" class="w-fit">
                        <Link :href="register()">
                            Create a free profile
                            <ArrowRight class="size-4" />
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="grid gap-3 p-6">
                    <Zap class="size-6 text-primary" />
                    <h3 class="text-xl font-semibold">Hiring talent?</h3>
                    <p class="text-sm text-muted-foreground">
                        Post jobs in minutes and search a growing pool of
                        candidates by skill, experience, and location.
                    </p>
                    <Button as-child class="w-fit">
                        <Link :href="register()">
                            Start hiring <ArrowRight class="size-4" />
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        </section>

        <p class="text-center text-xs text-muted-foreground">
            KerjaGo — the Southeast Asia recruitment marketplace.
        </p>
    </div>
</template>
