<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BriefcaseBusiness,
    CalendarClock,
    ChevronDown,
    ChevronUp,
    Clock,
    Download,
    Eye,
    GraduationCap,
    Languages,
    MapPin,
    MousePointerClick,
    Target,
    Wallet,
} from '@lucide/vue';
import { useTimeAgo } from '@vueuse/core';
import { computed, ref } from 'vue';
import LockedBadge from '@/components/LockedBadge.vue';
import SkillTags from '@/components/SkillTags.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { show } from '@/routes/employer/talent';
import {
    AVAILABILITY_LABELS,
    countryLabel,
    EDUCATION_LABELS,
    formatSalaryRange,
    GENDER_LABELS,
    LANGUAGE_LABELS,
    PROFICIENCY_LABELS,
    SALARY_PERIOD_LABELS,
} from '@/types/kerjago';
import type { TalentFilterForm, TalentSummary } from '@/types/kerjago';

/**
 * One search result. The whole card navigates to the candidate's profile;
 * only the show-more/show-less control stays on the page.
 *
 * Matching is shown at field level: a field the engine matched on (via
 * matchedFields) or one an active sidebar filter selected gets tinted whole —
 * no token-level marks.
 */
const props = defineProps<{
    profile: TalentSummary;
    matchedFields: string[];
    filters: TalentFilterForm;
}>();

const expanded = ref(true);

const initials = computed(() =>
    props.profile.full_name
        .split(/\s+/)
        .map((word) => word[0])
        .filter((char) => /[a-z]/i.test(char ?? ''))
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);

const lastActive = useTimeAgo(() => props.profile.last_active_at ?? '');

function matched(field: string): boolean {
    return props.matchedFields.includes(field);
}

const tints = computed(() => ({
    title: matched('current_title'),
    company: matched('current_company'),
    location:
        matched('location') ||
        props.filters.country.includes(props.profile.country),
    languages: props.profile.languages.some((spoken) =>
        props.filters.languages.includes(spoken.language),
    ),
    gender:
        props.profile.gender !== null &&
        props.filters.gender.includes(props.profile.gender),
    skills: matched('skills'),
    summary: matched('summary'),
    education:
        matched('education_level') ||
        (props.profile.education_level !== null &&
            props.filters.education_level.includes(
                props.profile.education_level,
            )),
    experience:
        props.filters.experience_band.length > 0 ||
        props.filters.experience_min !== '',
    availability:
        props.profile.availability !== null &&
        props.filters.availability.includes(props.profile.availability),
    preferred:
        matched('preferred_job_title') ||
        matched('preferred_location') ||
        (props.profile.preferred_country !== null &&
            props.filters.preferred_country.includes(
                props.profile.preferred_country,
            )),
}));

const TINT = 'font-semibold text-primary';
</script>

<template>
    <Link :href="show(profile.id)" class="group block">
        <Card
            class="transition-colors group-hover:border-primary/40"
            data-test="talent-card"
        >
            <CardContent class="grid gap-4">
                <div class="flex items-start gap-4">
                    <Avatar class="size-14 text-lg">
                        <AvatarFallback
                            class="bg-primary/10 font-medium text-primary"
                        >
                            {{ initials }}
                        </AvatarFallback>
                    </Avatar>

                    <div class="grid min-w-0 flex-1 gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="text-base font-semibold group-hover:text-primary"
                            >
                                {{ profile.full_name }}
                            </span>
                            <span
                                v-if="profile.age || profile.gender"
                                class="text-sm text-muted-foreground"
                                :class="{ [TINT]: tints.gender }"
                            >
                                {{
                                    [
                                        profile.age,
                                        profile.gender
                                            ? GENDER_LABELS[profile.gender]
                                            : null,
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')
                                }}
                            </span>
                            <LockedBadge v-if="profile.is_locked" />
                        </div>

                        <p class="truncate text-sm">
                            <span :class="tints.title ? TINT : 'font-medium'">
                                {{ profile.current_title }}
                            </span>
                            <span
                                v-if="profile.current_company"
                                class="text-muted-foreground"
                            >
                                at
                                <span :class="{ [TINT]: tints.company }">
                                    {{ profile.current_company }}
                                </span>
                            </span>
                        </p>

                        <div
                            class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground"
                        >
                            <span
                                class="flex items-center gap-1.5"
                                :class="{ [TINT]: tints.location }"
                            >
                                <MapPin class="size-3.5 shrink-0" />
                                {{ profile.city }},
                                {{ countryLabel(profile.country) }}
                            </span>
                            <span
                                v-if="profile.languages.length > 0"
                                class="flex items-center gap-1.5"
                                :class="{ [TINT]: tints.languages }"
                            >
                                <Languages class="size-3.5 shrink-0" />
                                {{
                                    profile.languages
                                        .map(
                                            (spoken) =>
                                                `${LANGUAGE_LABELS[spoken.language] ?? spoken.language} (${PROFICIENCY_LABELS[spoken.proficiency] ?? spoken.proficiency})`,
                                        )
                                        .join(', ')
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <template v-if="expanded">
                    <SkillTags
                        v-if="profile.skills.length > 0"
                        :skills="profile.skills"
                        :limit="6"
                        :highlighted="tints.skills"
                    />

                    <p
                        v-if="profile.summary"
                        class="line-clamp-2 text-sm"
                        :class="
                            tints.summary
                                ? 'font-medium text-primary'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ profile.summary }}
                    </p>

                    <div
                        class="grid gap-x-6 gap-y-2 text-sm text-muted-foreground sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <span
                            class="flex items-center gap-1.5"
                            :class="{ [TINT]: tints.experience }"
                        >
                            <BriefcaseBusiness class="size-3.5 shrink-0" />
                            {{ profile.experience_years }} years of experience
                        </span>
                        <span
                            v-if="profile.education_level"
                            class="flex items-center gap-1.5"
                            :class="{ [TINT]: tints.education }"
                        >
                            <GraduationCap class="size-3.5 shrink-0" />
                            {{ EDUCATION_LABELS[profile.education_level] }}
                        </span>
                        <span
                            v-if="
                                profile.expected_salary_min !== null &&
                                profile.expected_salary_max !== null &&
                                profile.expected_salary_currency
                            "
                            class="flex items-center gap-1.5"
                        >
                            <Wallet class="size-3.5 shrink-0" />
                            {{
                                formatSalaryRange(
                                    profile.expected_salary_min,
                                    profile.expected_salary_max,
                                    profile.expected_salary_currency,
                                )
                            }}
                            <template v-if="profile.expected_salary_period">
                                {{
                                    SALARY_PERIOD_LABELS[
                                        profile.expected_salary_period
                                    ]
                                }}
                            </template>
                        </span>
                        <span
                            v-if="profile.availability"
                            class="flex items-center gap-1.5"
                            :class="{ [TINT]: tints.availability }"
                        >
                            <CalendarClock class="size-3.5 shrink-0" />
                            {{ AVAILABILITY_LABELS[profile.availability] }}
                        </span>
                        <span
                            v-if="profile.preferred_job_title"
                            class="flex items-center gap-1.5"
                            :class="{ [TINT]: tints.preferred }"
                        >
                            <Target class="size-3.5 shrink-0" />
                            Open to {{ profile.preferred_job_title }}
                            <template v-if="profile.preferred_country">
                                in
                                {{
                                    [
                                        profile.preferred_city,
                                        countryLabel(profile.preferred_country),
                                    ]
                                        .filter(Boolean)
                                        .join(', ')
                                }}
                            </template>
                        </span>
                        <span
                            v-if="profile.last_active_at"
                            class="flex items-center gap-1.5"
                        >
                            <Clock class="size-3.5 shrink-0" />
                            Active {{ lastActive }}
                        </span>
                    </div>

                    <div
                        class="grid gap-2 rounded-lg bg-muted/50 p-3 text-sm sm:grid-cols-3"
                    >
                        <span class="flex items-center gap-1.5">
                            <Eye class="size-3.5 shrink-0" />
                            {{ profile.profile_views_count }} profile views
                        </span>
                        <span class="flex items-center gap-1.5">
                            <MousePointerClick class="size-3.5 shrink-0" />
                            {{ profile.employer_actions_count }} employer
                            actions
                        </span>
                        <span class="flex items-center gap-1.5">
                            <Download class="size-3.5 shrink-0" />
                            {{ profile.resume_downloads_count }} resume
                            downloads
                        </span>
                    </div>
                </template>

                <Button
                    variant="ghost"
                    size="sm"
                    class="justify-self-center text-muted-foreground"
                    @click.prevent.stop="expanded = !expanded"
                >
                    <template v-if="expanded">
                        <ChevronUp class="size-4" />
                        Show less
                    </template>
                    <template v-else>
                        <ChevronDown class="size-4" />
                        Show more
                    </template>
                </Button>
            </CardContent>
        </Card>
    </Link>
</template>
