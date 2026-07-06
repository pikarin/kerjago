<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { FileCheck2, Plus, Trash2 } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import SkillsInput from '@/components/SkillsInput.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import type { CountryCode, FacetOption } from '@/types/kerjago';

type ExperienceRow = {
    id: string | null;
    job_title: string;
    company_name: string;
    start_date: string;
    end_date: string;
    is_current: boolean;
};

const props = defineProps<{
    profile: {
        full_name: string;
        current_title: string;
        preferred_job_title: string | null;
        skills: string[];
        experience_years: number;
        country: string;
        city: string;
        preferred_country: string | null;
        preferred_city: string | null;
        availability: string | null;
        languages: string[];
        gender: string | null;
        education_level: string | null;
        phone: string | null;
        has_resume: boolean;
        experiences: {
            id: string;
            job_title: string;
            company_name: string;
            start_date: string;
            end_date: string | null;
        }[];
    } | null;
    countries: CountryCode[];
    availabilityOptions: FacetOption[];
    languageOptions: FacetOption[];
    genderOptions: FacetOption[];
    educationLevelOptions: FacetOption[];
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
    preferred_job_title: string;
    skills: string[];
    experience_years: number | '';
    country: string;
    city: string;
    preferred_country: string;
    preferred_city: string;
    availability: string;
    languages: string[];
    gender: string;
    education_level: string;
    phone: string;
    resume: File | null;
    experiences: ExperienceRow[];
}>({
    full_name: props.profile?.full_name ?? '',
    current_title: props.profile?.current_title ?? '',
    preferred_job_title: props.profile?.preferred_job_title ?? '',
    skills: props.profile?.skills ?? [],
    experience_years: props.profile?.experience_years ?? '',
    country: props.profile?.country ?? '',
    city: props.profile?.city ?? '',
    preferred_country: props.profile?.preferred_country ?? '',
    preferred_city: props.profile?.preferred_city ?? '',
    availability: props.profile?.availability ?? '',
    languages: props.profile?.languages ?? [],
    gender: props.profile?.gender ?? '',
    education_level: props.profile?.education_level ?? '',
    phone: props.profile?.phone ?? '',
    resume: null,
    experiences: (props.profile?.experiences ?? []).map((experience) => ({
        id: experience.id,
        job_title: experience.job_title,
        company_name: experience.company_name,
        start_date: experience.start_date.slice(0, 7),
        end_date: experience.end_date?.slice(0, 7) ?? '',
        is_current: experience.end_date === null,
    })),
});

function addExperience(): void {
    form.experiences.push({
        id: null,
        job_title: '',
        company_name: '',
        start_date: '',
        end_date: '',
        is_current: false,
    });
}

function removeExperience(index: number): void {
    form.experiences.splice(index, 1);
}

function toggleLanguage(value: string, checked: boolean): void {
    form.languages = checked
        ? [...form.languages, value]
        : form.languages.filter((language) => language !== value);
}

function experienceError(index: number, field: string): string | undefined {
    return (form.errors as Record<string, string>)[
        `experiences.${index}.${field}`
    ];
}

function handleResumeChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    form.resume = target.files?.[0] ?? null;
}

function submit(): void {
    form.transform((data) => ({
        ...data,
        preferred_job_title:
            data.preferred_job_title === '' ? null : data.preferred_job_title,
        preferred_country:
            data.preferred_country === '' ? null : data.preferred_country,
        preferred_city: data.preferred_city === '' ? null : data.preferred_city,
        availability: data.availability === '' ? null : data.availability,
        gender: data.gender === '' ? null : data.gender,
        education_level:
            data.education_level === '' ? null : data.education_level,
        phone: data.phone === '' ? null : data.phone,
        resume: data.resume ?? undefined,
        experiences: data.experiences.map(
            ({ is_current, start_date, end_date, ...experience }) => ({
                ...experience,
                start_date: start_date === '' ? '' : `${start_date}-01`,
                end_date:
                    is_current || end_date === '' ? null : `${end_date}-01`,
            }),
        ),
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
                <Label for="preferred_job_title">
                    Preferred job title (optional)
                </Label>
                <Input
                    id="preferred_job_title"
                    v-model="form.preferred_job_title"
                    type="text"
                    placeholder="The role you want next, e.g. Engineering Manager"
                />
                <InputError :message="form.errors.preferred_job_title" />
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

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Preferred country (optional)</Label>
                    <Select v-model="form.preferred_country">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Where you want to work" />
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
                    <InputError :message="form.errors.preferred_country" />
                </div>

                <div class="grid gap-2">
                    <Label for="preferred_city">
                        Preferred city (optional)
                    </Label>
                    <Input
                        id="preferred_city"
                        v-model="form.preferred_city"
                        type="text"
                    />
                    <InputError :message="form.errors.preferred_city" />
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-3">
                <div class="grid gap-2">
                    <Label>Availability (optional)</Label>
                    <Select v-model="form.availability">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="When can you start?" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in availabilityOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.availability" />
                </div>

                <div class="grid gap-2">
                    <Label>Gender (optional)</Label>
                    <Select v-model="form.gender">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in genderOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.gender" />
                </div>

                <div class="grid gap-2">
                    <Label>Education (optional)</Label>
                    <Select v-model="form.education_level">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Highest level" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in educationLevelOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.education_level" />
                </div>
            </div>

            <fieldset class="grid gap-2">
                <legend class="text-sm font-medium">
                    Languages (optional)
                </legend>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    <Label
                        v-for="option in languageOptions"
                        :key="option.value"
                        class="flex items-center gap-2 font-normal"
                    >
                        <Checkbox
                            :model-value="form.languages.includes(option.value)"
                            @update:model-value="
                                (checked) =>
                                    toggleLanguage(
                                        option.value,
                                        checked === true,
                                    )
                            "
                        />
                        {{ option.label }}
                    </Label>
                </div>
                <InputError :message="form.errors.languages" />
            </fieldset>

            <div class="grid gap-3">
                <div class="flex items-center justify-between">
                    <Label>Work experience (optional)</Label>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="addExperience"
                    >
                        <Plus class="size-4" />
                        Add experience
                    </Button>
                </div>

                <div
                    v-for="(experience, index) in form.experiences"
                    :key="experience.id ?? `new-${index}`"
                    class="grid gap-4 rounded-lg border p-4"
                >
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label :for="`experience_title_${index}`">
                                Job title
                            </Label>
                            <Input
                                :id="`experience_title_${index}`"
                                v-model="experience.job_title"
                                type="text"
                            />
                            <InputError
                                :message="experienceError(index, 'job_title')"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`experience_company_${index}`">
                                Company
                            </Label>
                            <Input
                                :id="`experience_company_${index}`"
                                v-model="experience.company_name"
                                type="text"
                            />
                            <InputError
                                :message="
                                    experienceError(index, 'company_name')
                                "
                            />
                        </div>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label :for="`experience_start_${index}`">
                                Start
                            </Label>
                            <Input
                                :id="`experience_start_${index}`"
                                v-model="experience.start_date"
                                type="month"
                            />
                            <InputError
                                :message="experienceError(index, 'start_date')"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`experience_end_${index}`">End</Label>
                            <Input
                                :id="`experience_end_${index}`"
                                v-model="experience.end_date"
                                type="month"
                                :disabled="experience.is_current"
                            />
                            <InputError
                                :message="experienceError(index, 'end_date')"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <Label class="flex items-center gap-2 font-normal">
                            <Checkbox v-model="experience.is_current" />
                            Current role
                        </Label>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="removeExperience(index)"
                        >
                            <Trash2 class="size-4" />
                            Remove
                        </Button>
                    </div>
                </div>

                <InputError :message="form.errors.experiences" />
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
