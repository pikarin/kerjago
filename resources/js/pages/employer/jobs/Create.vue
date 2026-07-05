<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import JobForm from '@/components/JobForm.vue';
import { dashboard } from '@/routes';
import { create, index, store } from '@/routes/employer/jobs';
import type {
    CountryCode,
    CurrencyCode,
    FacetOption,
    JobStatus,
} from '@/types/kerjago';

defineProps<{
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
            { title: 'Post a job', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="Post a job" />

    <div class="grid gap-6 p-4">
        <Heading
            title="Post a job"
            description="Describe the role — active jobs are instantly searchable by jobseekers."
        />

        <JobForm
            :initial="{
                title: '',
                description: '',
                skills: [],
                location_country: '',
                location_city: '',
                salary_min: '',
                salary_max: '',
                currency: '',
                employment_type: '',
                work_arrangement: '',
                experience_level: '',
                education_level: '',
                status: 'active',
            }"
            :submit-url="store().url"
            submit-method="post"
            :countries="countries"
            :currencies="currencies"
            :statuses="statuses"
            :employment-types="employmentTypes"
            :work-arrangements="workArrangements"
            :experience-levels="experienceLevels"
            :education-levels="educationLevels"
            submit-label="Post job"
        />
    </div>
</template>
