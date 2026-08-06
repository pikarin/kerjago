<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, Inbox, Lock, Mail, MapPin, Phone } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import LockedBadge from '@/components/LockedBadge.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import SkillTags from '@/components/SkillTags.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes';
import { resume } from '@/routes/applications';
import { update as updateStatus } from '@/routes/employer/applications/status';
import { index as jobsIndex } from '@/routes/employer/jobs';
import { show as talentShow } from '@/routes/employer/talent';
import { countryLabel } from '@/types/kerjago';
import type {
    ApplicantProfile,
    ApplicationStatus,
    JobStatus,
    Paginated,
} from '@/types/kerjago';

defineProps<{
    job: {
        id: string;
        title: string;
        status: JobStatus;
        expires_at: string | null;
    };
    unlockQuota: {
        used: number;
        total: number;
    };
    applications: Paginated<{
        id: string;
        status: ApplicationStatus;
        cover_note: string | null;
        has_resume: boolean;
        can_download_resume: boolean;
        applied_at: string | null;
        profile: ApplicantProfile;
    }>;
    statuses: ApplicationStatus[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'My jobs', href: jobsIndex() },
            { title: 'Applicants', href: '#' },
        ],
    },
});

function setStatus(applicationId: string, status: ApplicationStatus): void {
    router.patch(
        updateStatus(applicationId).url,
        { status },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="`Applicants – ${job.title}`" />

    <div class="grid gap-6 p-4">
        <div class="flex items-center gap-3">
            <Heading
                :title="`Applicants for ${job.title}`"
                description="Review candidates and move them through your pipeline."
            />
            <StatusBadge :status="job.status" />
        </div>

        <p class="text-sm text-muted-foreground">
            {{ unlockQuota.used }} of {{ unlockQuota.total }} free unlocks used
            on this job. The first {{ unlockQuota.total }} applicants are
            unlocked automatically; later applicants stay masked.
        </p>

        <EmptyState
            v-if="applications.data.length === 0"
            :icon="Inbox"
            title="No applicants yet"
            description="Once jobseekers apply, they'll show up here."
        />

        <div v-else class="grid gap-4">
            <Card
                v-for="application in applications.data"
                :key="application.id"
            >
                <CardHeader class="flex-row items-start justify-between gap-4">
                    <div class="grid gap-1">
                        <span class="flex items-center gap-2">
                            <Link
                                :href="talentShow(application.profile.id)"
                                class="font-medium hover:text-primary"
                            >
                                {{ application.profile.full_name }}
                            </Link>
                            <LockedBadge v-if="application.profile.is_locked" />
                        </span>
                        <p class="text-sm text-muted-foreground">
                            {{ application.profile.current_title }} ·
                            {{ application.profile.experience_years }} yrs
                            experience
                        </p>
                        <p
                            class="flex items-center gap-3 text-sm text-muted-foreground"
                        >
                            <span class="flex items-center gap-1">
                                <MapPin class="size-3.5" />
                                {{ application.profile.city }},
                                {{ countryLabel(application.profile.country) }}
                            </span>
                            <span
                                v-if="application.profile.phone"
                                class="flex items-center gap-1"
                                :class="{
                                    'opacity-60': application.profile.is_locked,
                                }"
                            >
                                <Phone class="size-3.5" />
                                {{ application.profile.phone }}
                            </span>
                            <span
                                v-if="application.profile.email"
                                class="flex items-center gap-1"
                                :class="{
                                    'opacity-60': application.profile.is_locked,
                                }"
                            >
                                <Mail class="size-3.5" />
                                {{ application.profile.email }}
                            </span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <StatusBadge :status="application.status" />
                        <Select
                            :model-value="application.status"
                            @update:model-value="
                                (value) =>
                                    setStatus(
                                        application.id,
                                        value as ApplicationStatus,
                                    )
                            "
                        >
                            <SelectTrigger class="w-36" size="sm">
                                <SelectValue placeholder="Set status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="status in statuses"
                                    :key="status"
                                    :value="status"
                                >
                                    {{
                                        status.charAt(0).toUpperCase() +
                                        status.slice(1)
                                    }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardHeader>
                <CardContent class="grid gap-3">
                    <SkillTags
                        :skills="application.profile.skills"
                        :limit="8"
                    />
                    <blockquote
                        v-if="application.cover_note"
                        class="border-l-2 pl-3 text-sm text-muted-foreground italic"
                    >
                        {{ application.cover_note }}
                    </blockquote>
                    <div class="flex items-center justify-between gap-4">
                        <p class="text-xs text-muted-foreground">
                            Applied {{ application.applied_at }}
                        </p>
                        <Button
                            v-if="application.can_download_resume"
                            as-child
                            variant="outline"
                            size="sm"
                        >
                            <a
                                :href="resume(application.id).url"
                                target="_blank"
                            >
                                <Download class="size-3.5" /> Resume
                            </a>
                        </Button>
                        <!-- The CV carries every field the mask withholds, so
                             it stays shut for a locked applicant. -->
                        <Button
                            v-else-if="application.has_resume"
                            variant="outline"
                            size="sm"
                            disabled
                            title="Unlock this candidate to open their resume"
                        >
                            <Lock class="size-3.5" /> Resume locked
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <PaginationNav :paginator="applications" />
    </div>
</template>
