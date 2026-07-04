<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileText } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { show as jobShow, index as jobsIndex } from '@/routes/jobs';
import { index } from '@/routes/jobseeker/applications';
import { countryLabel } from '@/types/kerjago';
import type {
    ApplicationStatus,
    CountryCode,
    JobStatus,
    Paginated,
} from '@/types/kerjago';

defineProps<{
    applications: Paginated<{
        id: string;
        status: ApplicationStatus;
        applied_at: string | null;
        job: {
            id: string;
            title: string;
            status: JobStatus;
            company_name: string;
            location_city: string;
            location_country: CountryCode;
        };
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'My applications', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="My applications" />

    <div class="grid gap-6 p-4">
        <Heading
            title="My applications"
            description="Track where you are in each company's pipeline."
        />

        <EmptyState
            v-if="applications.data.length === 0"
            :icon="FileText"
            title="No applications yet"
            description="Search open roles and submit your first application."
        >
            <Button as-child size="sm">
                <Link :href="jobsIndex()">Find jobs</Link>
            </Button>
        </EmptyState>

        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50 text-left">
                        <th class="px-4 py-3 font-medium">Job</th>
                        <th class="px-4 py-3 font-medium">Company</th>
                        <th class="px-4 py-3 font-medium">Location</th>
                        <th class="px-4 py-3 font-medium">Applied</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="application in applications.data"
                        :key="application.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="jobShow(application.job.id)"
                                class="font-medium hover:text-primary"
                            >
                                {{ application.job.title }}
                            </Link>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ application.job.company_name }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ application.job.location_city }},
                            {{ countryLabel(application.job.location_country) }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ application.applied_at }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="application.status" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <PaginationNav :paginator="applications" />
    </div>
</template>
