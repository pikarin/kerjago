<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import SkillsInput from '@/components/SkillsInput.vue';
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
import { countryLabel } from '@/types/kerjago';
import type {
    CountryCode,
    CurrencyCode,
    FacetOption,
    JobStatus,
} from '@/types/kerjago';

export type JobFormData = {
    title: string;
    description: string;
    skills: string[];
    location_country: string;
    location_city: string;
    salary_min: number | '';
    salary_max: number | '';
    currency: string;
    employment_type: string;
    work_arrangement: string;
    experience_level: string;
    education_level: string;
    status: string;
};

const props = defineProps<{
    initial: JobFormData;
    submitUrl: string;
    submitMethod: 'post' | 'put';
    countries: CountryCode[];
    currencies: CurrencyCode[];
    statuses: JobStatus[];
    employmentTypes: FacetOption[];
    workArrangements: FacetOption[];
    experienceLevels: FacetOption[];
    educationLevels: FacetOption[];
    submitLabel: string;
}>();

const form = useForm<JobFormData>({ ...props.initial });

function submit(): void {
    if (props.submitMethod === 'put') {
        form.put(props.submitUrl);
    } else {
        form.post(props.submitUrl);
    }
}
</script>

<template>
    <form class="grid max-w-2xl gap-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="title">Job title</Label>
            <Input
                id="title"
                v-model="form.title"
                type="text"
                placeholder="e.g. Senior Laravel Developer"
            />
            <InputError :message="form.errors.title" />
        </div>

        <div class="grid gap-2">
            <Label for="description">Description</Label>
            <textarea
                id="description"
                v-model="form.description"
                rows="10"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                placeholder="Role responsibilities, requirements, benefits…"
            />
            <InputError :message="form.errors.description" />
        </div>

        <div class="grid gap-2">
            <Label>Required skills</Label>
            <SkillsInput v-model="form.skills" />
            <InputError :message="form.errors.skills" />
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label>Country</Label>
                <Select v-model="form.location_country">
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
                <InputError :message="form.errors.location_country" />
            </div>

            <div class="grid gap-2">
                <Label for="location_city">City</Label>
                <Input
                    id="location_city"
                    v-model="form.location_city"
                    type="text"
                    placeholder="e.g. Jakarta"
                />
                <InputError :message="form.errors.location_city" />
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label>Currency</Label>
                <Select v-model="form.currency">
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Currency" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="currency in currencies"
                            :key="currency"
                            :value="currency"
                        >
                            {{ currency }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.currency" />
            </div>

            <div class="grid gap-2">
                <Label for="salary_min">Salary min</Label>
                <Input
                    id="salary_min"
                    v-model.number="form.salary_min"
                    type="number"
                    min="0"
                />
                <InputError :message="form.errors.salary_min" />
            </div>

            <div class="grid gap-2">
                <Label for="salary_max">Salary max</Label>
                <Input
                    id="salary_max"
                    v-model.number="form.salary_max"
                    type="number"
                    min="0"
                />
                <InputError :message="form.errors.salary_max" />
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label>Employment type</Label>
                <Select v-model="form.employment_type">
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select type" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in employmentTypes"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.employment_type" />
            </div>

            <div class="grid gap-2">
                <Label>Work arrangement</Label>
                <Select v-model="form.work_arrangement">
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select arrangement" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in workArrangements"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.work_arrangement" />
            </div>

            <div class="grid gap-2">
                <Label>Experience level</Label>
                <Select v-model="form.experience_level">
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select level" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in experienceLevels"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.experience_level" />
            </div>

            <div class="grid gap-2">
                <Label>Education</Label>
                <Select v-model="form.education_level">
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select education" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in educationLevels"
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

        <div class="grid gap-2">
            <Label>Status</Label>
            <Select v-model="form.status">
                <SelectTrigger class="w-48">
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="status in statuses"
                        :key="status"
                        :value="status"
                    >
                        {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <p class="text-xs text-muted-foreground">
                Only active jobs are visible and open for applications.
            </p>
            <InputError :message="form.errors.status" />
        </div>

        <div>
            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                {{ submitLabel }}
            </Button>
        </div>
    </form>
</template>
