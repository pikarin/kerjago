<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { FileCheck2 } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import SkillsInput from '@/components/SkillsInput.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { edit, update } from '@/routes/jobseeker/profile';
import { countryLabel } from '@/types/kerjago';
import type { CountryCode } from '@/types/kerjago';

const props = defineProps<{
    profile: {
        full_name: string;
        current_title: string;
        skills: string[];
        experience_years: number;
        country: string;
        city: string;
        phone: string | null;
        has_resume: boolean;
    } | null;
    countries: CountryCode[];
    status?: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'My profile', href: edit() },
        ],
    },
});

const form = useForm<{
    full_name: string;
    current_title: string;
    skills: string[];
    experience_years: number | '';
    country: string;
    city: string;
    phone: string;
    resume: File | null;
}>({
    full_name: props.profile?.full_name ?? '',
    current_title: props.profile?.current_title ?? '',
    skills: props.profile?.skills ?? [],
    experience_years: props.profile?.experience_years ?? '',
    country: props.profile?.country ?? '',
    city: props.profile?.city ?? '',
    phone: props.profile?.phone ?? '',
    resume: null,
});

function handleResumeChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    form.resume = target.files?.[0] ?? null;
}

function submit(): void {
    form.transform((data) => ({
        ...data,
        phone: data.phone === '' ? null : data.phone,
        resume: data.resume ?? undefined,
        _method: 'put',
    })).post(update().url);
}
</script>

<template>
    <Head title="My profile" />

    <div class="grid max-w-2xl gap-6 p-4">
        <Heading
            title="My profile"
            :description="
                profile
                    ? 'Keep your profile current — employers search it directly.'
                    : 'Complete your profile to apply for jobs and get discovered.'
            "
        />

        <Alert v-if="status">
            <AlertDescription>{{ status }}</AlertDescription>
        </Alert>

        <form class="grid gap-6" @submit.prevent="submit">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="full_name">Full name</Label>
                    <Input
                        id="full_name"
                        v-model="form.full_name"
                        type="text"
                    />
                    <InputError :message="form.errors.full_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="current_title">Current title</Label>
                    <Input
                        id="current_title"
                        v-model="form.current_title"
                        type="text"
                        placeholder="e.g. Backend Engineer"
                    />
                    <InputError :message="form.errors.current_title" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label>Skills</Label>
                <SkillsInput v-model="form.skills" />
                <InputError :message="form.errors.skills" />
            </div>

            <div class="grid gap-6 sm:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="experience_years">Years of experience</Label>
                    <Input
                        id="experience_years"
                        v-model.number="form.experience_years"
                        type="number"
                        min="0"
                        max="60"
                    />
                    <InputError :message="form.errors.experience_years" />
                </div>

                <div class="grid gap-2">
                    <Label>Country</Label>
                    <Select v-model="form.country">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select country" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="country in countries"
                                :key="country"
                                :value="country"
                            >
                                {{ countryLabel(country) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.country" />
                </div>

                <div class="grid gap-2">
                    <Label for="city">City</Label>
                    <Input id="city" v-model="form.city" type="text" />
                    <InputError :message="form.errors.city" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="phone">Phone (optional)</Label>
                <Input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    placeholder="+62 812 3456 7890"
                />
                <InputError :message="form.errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="resume">Resume (PDF or Word, max 5MB)</Label>
                <p
                    v-if="profile?.has_resume && !form.resume"
                    class="flex items-center gap-1.5 text-sm text-muted-foreground"
                >
                    <FileCheck2 class="size-4 text-emerald-600" />
                    A resume is on file. Upload a new one to replace it.
                </p>
                <Input
                    id="resume"
                    type="file"
                    accept=".pdf,.doc,.docx"
                    @change="handleResumeChange"
                />
                <InputError :message="form.errors.resume" />
            </div>

            <div>
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    Save profile
                </Button>
            </div>
        </form>
    </div>
</template>
