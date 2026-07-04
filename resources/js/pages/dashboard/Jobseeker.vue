<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileText, Search, UserRound } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { show as jobShow, index as jobsIndex } from '@/routes/jobs';
import { index as applicationsIndex } from '@/routes/jobseeker/applications';
import { edit as profileEdit } from '@/routes/jobseeker/profile';
import type { ApplicationStatus } from '@/types/kerjago';

defineProps<{
    hasProfile: boolean;
    stats: {
        total_applications: number;
        shortlisted: number;
    } | null;
    recentApplications: {
        id: string;
        status: ApplicationStatus;
        job_title: string;
        job_id: string;
        company_name: string;
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
                :icon="UserRound"
                title="Complete your profile"
                description="Add your skills, experience, and resume so you can apply for jobs and get discovered by employers."
            >
                <Button as-child>
                    <Link :href="profileEdit()">Complete profile</Link>
                </Button>
            </EmptyState>
        </template>

        <template v-else>
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold tracking-tight">Overview</h1>
                <Button as-child size="sm">
                    <Link :href="jobsIndex()"
                        ><Search class="size-4" /> Find jobs</Link
                    >
                </Button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Applications
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="text-3xl font-semibold">
                        {{ stats?.total_applications }}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Shortlisted
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="text-3xl font-semibold">
                        {{ stats?.shortlisted }}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader class="flex-row items-center justify-between">
                    <CardTitle class="text-base">Recent applications</CardTitle>
                    <Button as-child variant="ghost" size="sm">
                        <Link :href="applicationsIndex()">View all</Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <EmptyState
                        v-if="recentApplications.length === 0"
                        :icon="FileText"
                        title="No applications yet"
                        description="Search open roles and submit your first application."
                    >
                        <Button as-child size="sm">
                            <Link :href="jobsIndex()">Find jobs</Link>
                        </Button>
                    </EmptyState>
                    <ul v-else class="divide-y">
                        <li
                            v-for="application in recentApplications"
                            :key="application.id"
                            class="flex items-center justify-between gap-4 py-3"
                        >
                            <div class="grid gap-0.5">
                                <Link
                                    :href="jobShow(application.job_id)"
                                    class="font-medium hover:text-primary"
                                >
                                    {{ application.job_title }}
                                </Link>
                                <span class="text-sm text-muted-foreground">
                                    {{ application.company_name }}
                                </span>
                            </div>
                            <StatusBadge :status="application.status" />
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </template>
    </div>
</template>
