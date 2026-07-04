<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Briefcase, Plus, Users } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import {
    applicants,
    create as createJob,
    index as jobsIndex,
} from '@/routes/employer/jobs';
import { edit as profileEdit } from '@/routes/employer/profile';
import type { JobStatus } from '@/types/kerjago';

defineProps<{
    hasProfile: boolean;
    stats: {
        active_jobs: number;
        total_jobs: number;
        total_applicants: number;
    } | null;
    recentJobs: {
        id: string;
        title: string;
        status: JobStatus;
        applications_count: number;
    }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <template v-if="!hasProfile">
            <EmptyState
                :icon="Briefcase"
                title="Set up your company profile"
                description="Add your company details to start posting jobs and searching for talent."
            >
                <Button as-child>
                    <Link :href="profileEdit()">Set up company profile</Link>
                </Button>
            </EmptyState>
        </template>

        <template v-else>
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold tracking-tight">Overview</h1>
                <Button as-child size="sm">
                    <Link :href="createJob()"
                        ><Plus class="size-4" /> Post a job</Link
                    >
                </Button>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Active jobs
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="text-3xl font-semibold">
                        {{ stats?.active_jobs }}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Total jobs
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="text-3xl font-semibold">
                        {{ stats?.total_jobs }}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Total applicants
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="text-3xl font-semibold">
                        {{ stats?.total_applicants }}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader class="flex-row items-center justify-between">
                    <CardTitle class="text-base">Recent jobs</CardTitle>
                    <Button as-child variant="ghost" size="sm">
                        <Link :href="jobsIndex()">View all</Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <EmptyState
                        v-if="recentJobs.length === 0"
                        :icon="Briefcase"
                        title="No jobs yet"
                        description="Post your first job to start receiving applications."
                    >
                        <Button as-child size="sm">
                            <Link :href="createJob()">Post a job</Link>
                        </Button>
                    </EmptyState>
                    <ul v-else class="divide-y">
                        <li
                            v-for="job in recentJobs"
                            :key="job.id"
                            class="flex items-center justify-between gap-4 py-3"
                        >
                            <div class="flex items-center gap-3">
                                <span class="font-medium">{{ job.title }}</span>
                                <StatusBadge :status="job.status" />
                            </div>
                            <Button as-child variant="outline" size="sm">
                                <Link :href="applicants(job.id)">
                                    <Users class="size-3.5" />
                                    {{ job.applications_count }} applicants
                                </Link>
                            </Button>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </template>
    </div>
</template>
