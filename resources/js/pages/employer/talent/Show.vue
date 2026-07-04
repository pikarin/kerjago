<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BriefcaseBusiness, MapPin, Phone } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import SkillTags from '@/components/SkillTags.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index } from '@/routes/employer/talent';
import { countryLabel } from '@/types/kerjago';
import type { TalentProfile } from '@/types/kerjago';

defineProps<{
    profile: TalentProfile;
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
</script>

<template>
    <Head :title="profile.full_name" />

    <div class="grid max-w-3xl gap-6 p-4">
        <Heading
            :title="profile.full_name"
            :description="profile.current_title"
        />

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Candidate details</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4">
                <div class="grid gap-2 text-sm text-muted-foreground">
                    <p class="flex items-center gap-2">
                        <BriefcaseBusiness class="size-4" />
                        {{ profile.experience_years }} years of experience
                    </p>
                    <p class="flex items-center gap-2">
                        <MapPin class="size-4" />
                        {{ profile.city }}, {{ countryLabel(profile.country) }}
                    </p>
                    <p v-if="profile.phone" class="flex items-center gap-2">
                        <Phone class="size-4" /> {{ profile.phone }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <h3 class="text-sm font-medium">Skills</h3>
                    <SkillTags :skills="profile.skills" />
                </div>

                <p class="text-xs text-muted-foreground">
                    Resumes are shared when a candidate applies to one of your
                    jobs.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
