<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Briefcase, Pencil, Plus, Users } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { applicants, create, edit, index } from '@/routes/employer/jobs';
import { countryLabel } from '@/types/kerjago';
import type { CountryCode, JobStatus, Paginated } from '@/types/kerjago';

defineProps<{
    jobs: Paginated<{
        id: string;
        title: string;
        status: JobStatus;
        location_city: string;
        location_country: CountryCode;
        applications_count: number;
        created_at: string | null;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'My jobs', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="My jobs" />

    <div class="grid gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="My jobs"
                description="Manage your postings and review applicants."
            />
            <Button as-child size="sm">
                <Link :href="create()"><Plus class="size-4" /> Post a job</Link>
            </Button>
        </div>

        <EmptyState
            v-if="jobs.data.length === 0"
            :icon="Briefcase"
            title="No jobs posted yet"
            description="Your first posting is a few fields away."
        >
            <Button as-child size="sm">
                <Link :href="create()">Post a job</Link>
            </Button>
        </EmptyState>

        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50 text-left">
                        <th class="px-4 py-3 font-medium">Job</th>
                        <th class="px-4 py-3 font-medium">Location</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Applicants</th>
                        <th class="px-4 py-3 font-medium">Posted</th>
                        <th class="px-4 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="job in jobs.data"
                        :key="job.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3 font-medium">{{ job.title }}</td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ job.location_city }},
                            {{ countryLabel(job.location_country) }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="job.status" />
                        </td>
                        <td class="px-4 py-3">
                            <Link
                                :href="applicants(job.id)"
                                class="flex w-fit items-center gap-1.5 text-primary hover:underline"
                            >
                                <Users class="size-3.5" />
                                {{ job.applications_count }}
                            </Link>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ job.created_at }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="edit(job.id)"
                                    ><Pencil class="size-3.5" /> Edit</Link
                                >
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <PaginationNav :paginator="jobs" />
    </div>
</template>
