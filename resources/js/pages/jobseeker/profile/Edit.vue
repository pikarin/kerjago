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
    description: string;
    start_date: string;
    end_date: string;
    is_current: boolean;
};

type EducationRow = {
    id: string | null;
    institution: string;
    field_of_study: string;
    level: string;
    start_date: string;
    end_date: string;
};

type LanguageRow = {
    id: string | null;
    language: string;
    proficiency: string;
};

const props = defineProps<{
    profile: {
        full_name: string;
        current_title: string;
        current_company: string | null;
        preferred_job_title: string | null;
        summary: string | null;
        skills: string[];
        experience_years: number;
        country: string;
        state: string | null;
        city: string;
        preferred_country: string | null;
        preferred_state: string | null;
        preferred_city: string | null;
        expected_salary_min: number | null;
        expected_salary_max: number | null;
        expected_salary_currency: string | null;
        expected_salary_period: string | null;
        availability: string | null;
        gender: string | null;
        date_of_birth: string | null;
        phone: string | null;
        whatsapp: string | null;
        has_avatar: boolean;
        has_resume: boolean;
        experiences: {
            id: string;
            job_title: string;
            company_name: string;
            description: string | null;
            start_date: string;
            end_date: string | null;
            is_current: boolean;
        }[];
        educations: {
            id: string;
            institution: string;
            field_of_study: string | null;
            level: string | null;
            start_date: string | null;
            end_date: string | null;
        }[];
        languages: {
            id: string;
            language: string;
            proficiency: string;
        }[];
    } | null;
    countries: CountryCode[];
    availabilityOptions: FacetOption[];
    languageOptions: FacetOption[];
    languageProficiencyOptions: FacetOption[];
    genderOptions: FacetOption[];
    educationLevelOptions: FacetOption[];
    currencyOptions: string[];
    salaryPeriodOptions: FacetOption[];
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
    current_company: string;
    preferred_job_title: string;
    summary: string;
    skills: string[];
    experience_years: number | '';
    country: string;
    state: string;
    city: string;
    preferred_country: string;
    preferred_state: string;
    preferred_city: string;
    expected_salary_min: number | '';
    expected_salary_max: number | '';
    expected_salary_currency: string;
    expected_salary_period: string;
    availability: string;
    gender: string;
    date_of_birth: string;
    phone: string;
    whatsapp: string;
    avatar: File | null;
    resume: File | null;
    experiences: ExperienceRow[];
    educations: EducationRow[];
    languages: LanguageRow[];
}>({
    full_name: props.profile?.full_name ?? '',
    current_title: props.profile?.current_title ?? '',
    current_company: props.profile?.current_company ?? '',
    preferred_job_title: props.profile?.preferred_job_title ?? '',
    summary: props.profile?.summary ?? '',
    skills: props.profile?.skills ?? [],
    experience_years: props.profile?.experience_years ?? '',
    country: props.profile?.country ?? '',
    state: props.profile?.state ?? '',
    city: props.profile?.city ?? '',
    preferred_country: props.profile?.preferred_country ?? '',
    preferred_state: props.profile?.preferred_state ?? '',
    preferred_city: props.profile?.preferred_city ?? '',
    expected_salary_min: props.profile?.expected_salary_min ?? '',
    expected_salary_max: props.profile?.expected_salary_max ?? '',
    expected_salary_currency: props.profile?.expected_salary_currency ?? '',
    expected_salary_period: props.profile?.expected_salary_period ?? '',
    availability: props.profile?.availability ?? '',
    gender: props.profile?.gender ?? '',
    date_of_birth: props.profile?.date_of_birth ?? '',
    phone: props.profile?.phone ?? '',
    whatsapp: props.profile?.whatsapp ?? '',
    avatar: null,
    resume: null,
    experiences: (props.profile?.experiences ?? []).map((experience) => ({
        id: experience.id,
        job_title: experience.job_title,
        company_name: experience.company_name,
        description: experience.description ?? '',
        start_date: experience.start_date.slice(0, 7),
        end_date: experience.end_date?.slice(0, 7) ?? '',
        is_current: experience.is_current,
    })),
    educations: (props.profile?.educations ?? []).map((education) => ({
        id: education.id,
        institution: education.institution,
        field_of_study: education.field_of_study ?? '',
        level: education.level ?? '',
        start_date: education.start_date?.slice(0, 7) ?? '',
        end_date: education.end_date?.slice(0, 7) ?? '',
    })),
    languages: (props.profile?.languages ?? []).map((language) => ({
        id: language.id,
        language: language.language,
        proficiency: language.proficiency,
    })),
});

function addExperience(): void {
    form.experiences.push({
        id: null,
        job_title: '',
        company_name: '',
        description: '',
        start_date: '',
        end_date: '',
        is_current: false,
    });
}

function removeExperience(index: number): void {
    form.experiences.splice(index, 1);
}

function addEducation(): void {
    form.educations.push({
        id: null,
        institution: '',
        field_of_study: '',
        level: '',
        start_date: '',
        end_date: '',
    });
}

function removeEducation(index: number): void {
    form.educations.splice(index, 1);
}

function addLanguage(): void {
    form.languages.push({ id: null, language: '', proficiency: '' });
}

function removeLanguage(index: number): void {
    form.languages.splice(index, 1);
}

function rowError(
    collection: string,
    index: number,
    field: string,
): string | undefined {
    return (form.errors as Record<string, string>)[
        `${collection}.${index}.${field}`
    ];
}

function handleAvatarChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    form.avatar = target.files?.[0] ?? null;
}

function handleResumeChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    form.resume = target.files?.[0] ?? null;
}

/** Empty select/text inputs post as `''`; the API expects `null`. */
function orNull(value: string | number): string | number | null {
    return value === '' ? null : value;
}

/** A `type="month"` value is `YYYY-MM`; the API expects a full date. */
function orFirstOfMonth(value: string): string | null {
    return value === '' ? null : `${value}-01`;
}

function submit(): void {
    form.transform((data) => ({
        ...data,
        current_company: orNull(data.current_company),
        preferred_job_title: orNull(data.preferred_job_title),
        summary: orNull(data.summary),
        state: orNull(data.state),
        preferred_country: orNull(data.preferred_country),
        preferred_state: orNull(data.preferred_state),
        preferred_city: orNull(data.preferred_city),
        expected_salary_min: orNull(data.expected_salary_min),
        expected_salary_max: orNull(data.expected_salary_max),
        expected_salary_currency: orNull(data.expected_salary_currency),
        expected_salary_period: orNull(data.expected_salary_period),
        availability: orNull(data.availability),
        gender: orNull(data.gender),
        date_of_birth: orNull(data.date_of_birth),
        phone: orNull(data.phone),
        whatsapp: orNull(data.whatsapp),
        avatar: data.avatar ?? undefined,
        resume: data.resume ?? undefined,
        experiences: data.experiences.map(
            ({ description, start_date, end_date, is_current, ...rest }) => ({
                ...rest,
                description: orNull(description),
                start_date: start_date === '' ? '' : `${start_date}-01`,
                end_date: is_current ? null : orFirstOfMonth(end_date),
                is_current,
            }),
        ),
        educations: data.educations.map(
            ({ field_of_study, level, start_date, end_date, ...rest }) => ({
                ...rest,
                field_of_study: orNull(field_of_study),
                level: orNull(level),
                start_date: orFirstOfMonth(start_date),
                end_date: orFirstOfMonth(end_date),
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
                    <Label for="date_of_birth">
                        Date of birth (optional)
                    </Label>
                    <Input
                        id="date_of_birth"
                        v-model="form.date_of_birth"
                        type="date"
                    />
                    <InputError :message="form.errors.date_of_birth" />
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
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

                <div class="grid gap-2">
                    <Label for="current_company">
                        Current company (optional)
                    </Label>
                    <Input
                        id="current_company"
                        v-model="form.current_company"
                        type="text"
                    />
                    <InputError :message="form.errors.current_company" />
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
                <Label for="summary">Profile summary (optional)</Label>
                <textarea
                    id="summary"
                    v-model="form.summary"
                    rows="4"
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                    placeholder="A short pitch employers read first."
                />
                <InputError :message="form.errors.summary" />
            </div>

            <div class="grid gap-2">
                <Label>Skills</Label>
                <SkillsInput v-model="form.skills" />
                <InputError :message="form.errors.skills" />
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
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
            </div>

            <div class="grid gap-6 sm:grid-cols-3">
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
                    <Label for="state">State/province (optional)</Label>
                    <Input id="state" v-model="form.state" type="text" />
                    <InputError :message="form.errors.state" />
                </div>

                <div class="grid gap-2">
                    <Label for="city">City</Label>
                    <Input id="city" v-model="form.city" type="text" />
                    <InputError :message="form.errors.city" />
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-3">
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
                    <Label for="preferred_state">
                        Preferred state (optional)
                    </Label>
                    <Input
                        id="preferred_state"
                        v-model="form.preferred_state"
                        type="text"
                    />
                    <InputError :message="form.errors.preferred_state" />
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

            <fieldset class="grid gap-3">
                <legend class="text-sm font-medium">
                    Expected salary (optional)
                </legend>
                <div class="grid gap-6 sm:grid-cols-4">
                    <div class="grid gap-2">
                        <Label for="expected_salary_min">Minimum</Label>
                        <Input
                            id="expected_salary_min"
                            v-model.number="form.expected_salary_min"
                            type="number"
                            min="0"
                        />
                        <InputError
                            :message="form.errors.expected_salary_min"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="expected_salary_max">Maximum</Label>
                        <Input
                            id="expected_salary_max"
                            v-model.number="form.expected_salary_max"
                            type="number"
                            min="0"
                        />
                        <InputError
                            :message="form.errors.expected_salary_max"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label>Currency</Label>
                        <Select v-model="form.expected_salary_currency">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Currency" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="currency in currencyOptions"
                                    :key="currency"
                                    :value="currency"
                                >
                                    {{ currency }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            :message="form.errors.expected_salary_currency"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label>Period</Label>
                        <Select v-model="form.expected_salary_period">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Per" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in salaryPeriodOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            :message="form.errors.expected_salary_period"
                        />
                    </div>
                </div>
            </fieldset>

            <div class="grid gap-2 sm:w-1/3">
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

            <div class="grid gap-3">
                <div class="flex items-center justify-between">
                    <Label>Languages (optional)</Label>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="addLanguage"
                    >
                        <Plus class="size-4" />
                        Add language
                    </Button>
                </div>

                <div
                    v-for="(language, index) in form.languages"
                    :key="language.id ?? `new-${index}`"
                    class="grid items-end gap-4 rounded-lg border p-4 sm:grid-cols-[1fr_1fr_auto]"
                >
                    <div class="grid gap-2">
                        <Label>Language</Label>
                        <Select v-model="language.language">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Select" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in languageOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            :message="rowError('languages', index, 'language')"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label>Proficiency</Label>
                        <Select v-model="language.proficiency">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Select" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in languageProficiencyOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            :message="
                                rowError('languages', index, 'proficiency')
                            "
                        />
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="removeLanguage(index)"
                    >
                        <Trash2 class="size-4" />
                        Remove
                    </Button>
                </div>

                <InputError :message="form.errors.languages" />
            </div>

            <div class="grid gap-3">
                <div class="flex items-center justify-between">
                    <Label>Education (optional)</Label>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="addEducation"
                    >
                        <Plus class="size-4" />
                        Add education
                    </Button>
                </div>

                <div
                    v-for="(education, index) in form.educations"
                    :key="education.id ?? `new-${index}`"
                    class="grid gap-4 rounded-lg border p-4"
                >
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label :for="`education_institution_${index}`">
                                Institution
                            </Label>
                            <Input
                                :id="`education_institution_${index}`"
                                v-model="education.institution"
                                type="text"
                            />
                            <InputError
                                :message="
                                    rowError('educations', index, 'institution')
                                "
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`education_field_${index}`">
                                Field of study
                            </Label>
                            <Input
                                :id="`education_field_${index}`"
                                v-model="education.field_of_study"
                                type="text"
                            />
                            <InputError
                                :message="
                                    rowError(
                                        'educations',
                                        index,
                                        'field_of_study',
                                    )
                                "
                            />
                        </div>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label>Level</Label>
                            <Select v-model="education.level">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select" />
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
                            <InputError
                                :message="
                                    rowError('educations', index, 'level')
                                "
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`education_start_${index}`">
                                Start
                            </Label>
                            <Input
                                :id="`education_start_${index}`"
                                v-model="education.start_date"
                                type="month"
                            />
                            <InputError
                                :message="
                                    rowError('educations', index, 'start_date')
                                "
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`education_end_${index}`">End</Label>
                            <Input
                                :id="`education_end_${index}`"
                                v-model="education.end_date"
                                type="month"
                            />
                            <InputError
                                :message="
                                    rowError('educations', index, 'end_date')
                                "
                            />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="removeEducation(index)"
                        >
                            <Trash2 class="size-4" />
                            Remove
                        </Button>
                    </div>
                </div>

                <InputError :message="form.errors.educations" />
            </div>

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
                                :message="
                                    rowError('experiences', index, 'job_title')
                                "
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
                                    rowError(
                                        'experiences',
                                        index,
                                        'company_name',
                                    )
                                "
                            />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`experience_description_${index}`">
                            What you did
                        </Label>
                        <textarea
                            :id="`experience_description_${index}`"
                            v-model="experience.description"
                            rows="3"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        />
                        <InputError
                            :message="
                                rowError('experiences', index, 'description')
                            "
                        />
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
                                :message="
                                    rowError('experiences', index, 'start_date')
                                "
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
                                :message="
                                    rowError('experiences', index, 'end_date')
                                "
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

            <div class="grid gap-6 sm:grid-cols-2">
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
                    <Label for="whatsapp">WhatsApp (optional)</Label>
                    <Input
                        id="whatsapp"
                        v-model="form.whatsapp"
                        type="tel"
                        placeholder="+62 812 3456 7890"
                    />
                    <InputError :message="form.errors.whatsapp" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="avatar">Profile photo (image, max 2MB)</Label>
                <p
                    v-if="profile?.has_avatar && !form.avatar"
                    class="flex items-center gap-1.5 text-sm text-muted-foreground"
                >
                    <FileCheck2 class="size-4 text-emerald-600" />
                    A photo is on file. Upload a new one to replace it.
                </p>
                <Input
                    id="avatar"
                    type="file"
                    accept="image/*"
                    @change="handleAvatarChange"
                />
                <InputError :message="form.errors.avatar" />
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
