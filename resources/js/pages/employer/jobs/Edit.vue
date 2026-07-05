<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import JobForm from '@/components/JobForm.vue';
import { dashboard } from '@/routes';
import { index, update } from '@/routes/employer/jobs';
import type {
    CountryCode,
    CurrencyCode,
    FacetOption,
    JobStatus,
} from '@/types/kerjago';

const props = defineProps<{
    job: {
        id: string;
        title: string;
        description: string;
        skills: string[];
        location_country: string;
        location_city: string;
        salary_min: number;
        salary_max: number;
        currency: string;
        employment_type: string;
        work_arrangement: string;
        experience_level: string;
        education_level: string;
        status: JobStatus;
    };
    countries: CountryCode[];
    currencies: CurrencyCode[];
    statuses: JobStatus[];
    employmentTypes: FacetOption[];
    workArrangements: FacetOption[];
    experienceLevels: FacetOption[];
    educationLevels: FacetOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'My jobs', href: index() },
            { title: 'Edit job', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Edit job" />

    <div class="grid gap-6 p-4">
        <Heading
            :title="`Edit: ${job.title}`"
            description="Update the role or change its status."
        />

        <JobForm
            :initial="{
                title: job.title,
                description: job.description,
                skills: job.skills,
                location_country: job.location_country,
                location_city: job.location_city,
                salary_min: job.salary_min,
                salary_max: job.salary_max,
                currency: job.currency,
                employment_type: job.employment_type,
                work_arrangement: job.work_arrangement,
                experience_level: job.experience_level,
                education_level: job.education_level,
                status: job.status,
            }"
            :submit-url="update(props.job.id).url"
            submit-method="put"
            :countries="countries"
            :currencies="currencies"
            :statuses="statuses"
            :employment-types="employmentTypes"
            :work-arrangements="workArrangements"
            :experience-levels="experienceLevels"
            :education-levels="educationLevels"
            submit-label="Save changes"
        />
    </div>
</template>
