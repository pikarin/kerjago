<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Banknote, Building2, MapPin } from '@lucide/vue';
import SkillTags from '@/components/SkillTags.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { show } from '@/routes/jobs';
import { countryLabel, formatSalaryRange } from '@/types/kerjago';
import type { JobSummary } from '@/types/kerjago';

defineProps<{
    job: JobSummary;
}>();
</script>

<template>
    <Link :href="show(job.id)" class="group">
        <Card class="h-full transition-shadow group-hover:shadow-md">
            <CardHeader>
                <CardTitle
                    class="text-base leading-snug group-hover:text-primary"
                >
                    {{ job.title }}
                </CardTitle>
                <p
                    class="flex items-center gap-1.5 text-sm text-muted-foreground"
                >
                    <Building2 class="size-3.5" />
                    {{ job.company_name }}
                </p>
            </CardHeader>
            <CardContent class="grid gap-3">
                <div class="grid gap-1.5 text-sm text-muted-foreground">
                    <p class="flex items-center gap-1.5">
                        <MapPin class="size-3.5" />
                        {{ job.location_city }},
                        {{ countryLabel(job.location_country) }}
                    </p>
                    <p class="flex items-center gap-1.5">
                        <Banknote class="size-3.5" />
                        {{
                            formatSalaryRange(
                                job.salary_min,
                                job.salary_max,
                                job.currency,
                            )
                        }}
                    </p>
                </div>
                <SkillTags :skills="job.skills" :limit="4" />
                <p v-if="job.posted_at" class="text-xs text-muted-foreground">
                    Posted {{ job.posted_at }}
                </p>
            </CardContent>
        </Card>
    </Link>
</template>
