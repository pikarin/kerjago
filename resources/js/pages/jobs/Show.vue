<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Banknote, Building2, CheckCircle2, Globe, MapPin } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import SkillTags from '@/components/SkillTags.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { apply } from '@/routes/jobseeker/jobs';
import { edit as profileEdit } from '@/routes/jobseeker/profile';
import { countryLabel, formatSalaryRange } from '@/types/kerjago';
import type { JobDetail } from '@/types/kerjago';

const props = defineProps<{
    job: JobDetail;
    viewer: {
        is_jobseeker: boolean;
        has_profile: boolean;
        has_applied: boolean;
    };
}>();

const dialogOpen = ref(false);

const form = useForm({
    cover_note: '',
});

function submitApplication(): void {
    form.post(apply(props.job.id).url, {
        onSuccess: () => {
            dialogOpen.value = false;
        },
    });
}
</script>

<template>
    <Head :title="job.title" />

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="grid content-start gap-6">
            <div class="grid gap-3">
                <h1 class="text-3xl font-semibold tracking-tight">
                    {{ job.title }}
                </h1>
                <div
                    class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground"
                >
                    <span class="flex items-center gap-1.5">
                        <Building2 class="size-4" /> {{ job.company.name }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <MapPin class="size-4" />
                        {{ job.location_city }},
                        {{ countryLabel(job.location_country) }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <Banknote class="size-4" />
                        {{
                            formatSalaryRange(
                                job.salary_min,
                                job.salary_max,
                                job.currency,
                            )
                        }}
                    </span>
                </div>
                <SkillTags :skills="job.skills" />
                <p v-if="job.posted_at" class="text-xs text-muted-foreground">
                    Posted {{ job.posted_at }}
                </p>
            </div>

            <div class="text-sm leading-relaxed whitespace-pre-line">
                {{ job.description }}
            </div>
        </div>

        <div class="grid content-start gap-4">
            <Card>
                <CardContent class="grid gap-3 pt-2">
                    <template v-if="viewer.has_applied">
                        <Badge
                            variant="outline"
                            class="justify-center gap-1.5 border-transparent bg-emerald-100 py-2 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                        >
                            <CheckCircle2 class="size-4" /> Application
                            submitted
                        </Badge>
                    </template>

                    <template
                        v-else-if="viewer.is_jobseeker && viewer.has_profile"
                    >
                        <Dialog v-model:open="dialogOpen">
                            <DialogTrigger as-child>
                                <Button class="w-full" size="lg"
                                    >Apply now</Button
                                >
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle
                                        >Apply to {{ job.title }}</DialogTitle
                                    >
                                    <DialogDescription>
                                        Your profile and resume will be shared
                                        with
                                        {{ job.company.name }}.
                                    </DialogDescription>
                                </DialogHeader>
                                <div class="grid gap-2">
                                    <Label for="cover_note"
                                        >Cover note (optional)</Label
                                    >
                                    <textarea
                                        id="cover_note"
                                        v-model="form.cover_note"
                                        rows="5"
                                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                        placeholder="Tell them why you're a great fit…"
                                    />
                                    <InputError
                                        :message="form.errors.cover_note"
                                    />
                                    <InputError
                                        :message="
                                            (
                                                form.errors as Record<
                                                    string,
                                                    string
                                                >
                                            ).job
                                        "
                                    />
                                </div>
                                <DialogFooter>
                                    <Button
                                        :disabled="form.processing"
                                        @click="submitApplication"
                                    >
                                        <Spinner v-if="form.processing" />
                                        Submit application
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </template>

                    <template v-else-if="viewer.is_jobseeker">
                        <p class="text-sm text-muted-foreground">
                            Complete your profile before applying.
                        </p>
                        <Button as-child class="w-full">
                            <Link :href="profileEdit()">Complete profile</Link>
                        </Button>
                    </template>

                    <template v-else>
                        <p class="text-sm text-muted-foreground">
                            Log in as a jobseeker to apply for this role.
                        </p>
                        <Button as-child class="w-full">
                            <Link :href="login()">Log in to apply</Link>
                        </Button>
                    </template>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base"
                        >About {{ job.company.name }}</CardTitle
                    >
                </CardHeader>
                <CardContent class="grid gap-2 text-sm text-muted-foreground">
                    <p>{{ job.company.industry }}</p>
                    <p class="flex items-center gap-1.5">
                        <MapPin class="size-3.5" />
                        {{ job.company.city }},
                        {{ countryLabel(job.company.country) }}
                    </p>
                    <a
                        v-if="job.company.website"
                        :href="job.company.website"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-1.5 text-primary hover:underline"
                    >
                        <Globe class="size-3.5" /> Website
                    </a>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
