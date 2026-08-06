<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    BriefcaseBusiness,
    CalendarClock,
    GraduationCap,
    Languages,
    Mail,
    MapPin,
    MessageCircle,
    Phone,
    Target,
    Wallet,
} from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import LockedBadge from '@/components/LockedBadge.vue';
import SkillTags from '@/components/SkillTags.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index } from '@/routes/employer/talent';
import { countryLabel, formatSalaryRange } from '@/types/kerjago';
import type { TalentDetail } from '@/types/kerjago';

defineProps<{
    profile: TalentDetail;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Talent search', href: index() },
            { title: 'Candidate', href: '#' },
        ],
    },
});

const AVAILABILITY_LABELS: Record<string, string> = {
    immediately: 'Available immediately',
    two_weeks: 'Available in 2 weeks',
    one_month: 'Available in 1 month',
    two_months_plus: 'Available in 2+ months',
};

const EDUCATION_LABELS: Record<string, string> = {
    none: 'No formal education',
    high_school: 'High school',
    diploma: 'Diploma',
    bachelor: "Bachelor's degree",
    master: "Master's degree",
    doctorate: 'Doctorate',
};

const PERIOD_LABELS: Record<string, string> = {
    monthly: 'per month',
    yearly: 'per year',
    hourly: 'per hour',
};

const PROFICIENCY_LABELS: Record<string, string> = {
    basic: 'Basic',
    good: 'Good',
    fluent: 'Fluent',
    native: 'Native',
};

const LANGUAGE_LABELS: Record<string, string> = {
    id: 'Indonesian',
    en: 'English',
    ms: 'Malay',
    zh: 'Mandarin',
    th: 'Thai',
    vi: 'Vietnamese',
    tl: 'Tagalog',
};

function formatMonth(value: string): string {
    const [year, month] = value.split('-');

    return new Date(Number(year), Number(month) - 1).toLocaleDateString('en', {
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <Head :title="profile.full_name" />

    <div class="grid max-w-3xl gap-6 p-4">
        <Heading
            :title="
                [profile.full_name, profile.age ? `${profile.age}` : null]
                    .filter(Boolean)
                    .join(', ')
            "
            :description="
                profile.current_company
                    ? `${profile.current_title} at ${profile.current_company}`
                    : profile.current_title
            "
        />

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    Contact details
                    <LockedBadge v-if="profile.is_locked" />
                </CardTitle>
            </CardHeader>
            <CardContent class="grid gap-2 text-sm text-muted-foreground">
                <p
                    v-if="profile.is_locked"
                    class="text-sm text-muted-foreground"
                >
                    This candidate is locked. Their full name and contact
                    details unlock automatically when they are among the first
                    ten applicants to one of your jobs.
                </p>
                <p
                    v-if="profile.email"
                    class="flex items-center gap-2"
                    :class="{ 'opacity-60': profile.is_locked }"
                >
                    <Mail class="size-4" />
                    {{ profile.email }}
                </p>
                <p
                    v-if="profile.phone"
                    class="flex items-center gap-2"
                    :class="{ 'opacity-60': profile.is_locked }"
                >
                    <Phone class="size-4" />
                    {{ profile.phone }}
                </p>
                <p
                    v-if="profile.whatsapp"
                    class="flex items-center gap-2"
                    :class="{ 'opacity-60': profile.is_locked }"
                >
                    <MessageCircle class="size-4" />
                    {{ profile.whatsapp }}
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Candidate details</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4">
                <p v-if="profile.summary" class="text-sm">
                    {{ profile.summary }}
                </p>

                <div class="grid gap-2 text-sm text-muted-foreground">
                    <p
                        v-if="profile.preferred_job_title"
                        class="flex items-center gap-2"
                    >
                        <Target class="size-4" />
                        Looking for: {{ profile.preferred_job_title }}
                        <template
                            v-if="
                                profile.preferred_city ||
                                profile.preferred_country
                            "
                        >
                            in
                            {{
                                [
                                    profile.preferred_city,
                                    profile.preferred_country
                                        ? countryLabel(
                                              profile.preferred_country,
                                          )
                                        : null,
                                ]
                                    .filter(Boolean)
                                    .join(', ')
                            }}
                        </template>
                    </p>
                    <p class="flex items-center gap-2">
                        <BriefcaseBusiness class="size-4" />
                        {{ profile.experience_years }} years of experience
                    </p>
                    <p class="flex items-center gap-2">
                        <MapPin class="size-4" />
                        {{
                            [
                                profile.city,
                                profile.state,
                                countryLabel(profile.country),
                            ]
                                .filter(Boolean)
                                .join(', ')
                        }}
                    </p>
                    <p
                        v-if="
                            profile.expected_salary_min !== null &&
                            profile.expected_salary_max !== null &&
                            profile.expected_salary_currency
                        "
                        class="flex items-center gap-2"
                    >
                        <Wallet class="size-4" />
                        {{
                            formatSalaryRange(
                                profile.expected_salary_min,
                                profile.expected_salary_max,
                                profile.expected_salary_currency,
                            )
                        }}
                        <span v-if="profile.expected_salary_period">
                            {{ PERIOD_LABELS[profile.expected_salary_period] }}
                        </span>
                    </p>
                    <p
                        v-if="profile.availability"
                        class="flex items-center gap-2"
                    >
                        <CalendarClock class="size-4" />
                        {{ AVAILABILITY_LABELS[profile.availability] }}
                    </p>
                    <p
                        v-if="profile.education_level"
                        class="flex items-center gap-2"
                    >
                        <GraduationCap class="size-4" />
                        {{ EDUCATION_LABELS[profile.education_level] }}
                    </p>
                    <p
                        v-if="profile.languages.length > 0"
                        class="flex items-center gap-2"
                    >
                        <Languages class="size-4" />
                        {{
                            profile.languages
                                .map(
                                    (spoken) =>
                                        `${LANGUAGE_LABELS[spoken.language] ?? spoken.language} (${PROFICIENCY_LABELS[spoken.proficiency] ?? spoken.proficiency})`,
                                )
                                .join(', ')
                        }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <h3 class="text-sm font-medium">Skills</h3>
                    <SkillTags :skills="profile.skills" />
                </div>

                <p class="text-xs text-muted-foreground">
                    Resumes and contact details are shared when a candidate
                    applies to one of your jobs.
                </p>
            </CardContent>
        </Card>

        <Card v-if="profile.work_experiences.length > 0">
            <CardHeader>
                <CardTitle class="text-base">Work experience</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4">
                <div
                    v-for="experience in profile.work_experiences"
                    :key="experience.id"
                    class="grid gap-0.5 border-l-2 border-muted pl-4"
                >
                    <p class="text-sm font-medium">
                        {{ experience.job_title }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ experience.company_name }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ formatMonth(experience.start_date) }} –
                        {{
                            experience.is_current
                                ? 'Present'
                                : experience.end_date
                                  ? formatMonth(experience.end_date)
                                  : ''
                        }}
                    </p>
                    <p
                        v-if="experience.description"
                        class="mt-1 text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ experience.description }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <Card v-if="profile.educations.length > 0">
            <CardHeader>
                <CardTitle class="text-base">Education</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4">
                <div
                    v-for="education in profile.educations"
                    :key="education.id"
                    class="grid gap-0.5 border-l-2 border-muted pl-4"
                >
                    <p class="text-sm font-medium">
                        {{ education.institution }}
                    </p>
                    <p
                        v-if="education.field_of_study || education.level"
                        class="text-sm text-muted-foreground"
                    >
                        {{
                            [
                                education.field_of_study,
                                education.level
                                    ? EDUCATION_LABELS[education.level]
                                    : null,
                            ]
                                .filter(Boolean)
                                .join(' · ')
                        }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{
                            education.start_date
                                ? formatMonth(education.start_date)
                                : 'Unspecified'
                        }}
                        –
                        {{
                            education.end_date
                                ? formatMonth(education.end_date)
                                : 'Unspecified'
                        }}
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
