<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { edit, update } from '@/routes/employer/profile';
import { countryLabel } from '@/types/kerjago';
import type { CountryCode } from '@/types/kerjago';

const props = defineProps<{
    profile: {
        company_name: string;
        industry: string;
        country: string;
        city: string;
        website: string | null;
    } | null;
    countries: CountryCode[];
    status?: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Company profile', href: edit() },
        ],
    },
});

const form = useForm({
    company_name: props.profile?.company_name ?? '',
    industry: props.profile?.industry ?? '',
    country: props.profile?.country ?? '',
    city: props.profile?.city ?? '',
    website: props.profile?.website ?? '',
});

function submit(): void {
    form.transform((data) => ({
        ...data,
        website: data.website === '' ? null : data.website,
    })).put(update().url);
}
</script>

<template>
    <Head title="Company profile" />

    <div class="grid max-w-2xl gap-6 p-4">
        <Heading
            title="Company profile"
            :description="
                profile
                    ? 'Update your company details shown on job postings.'
                    : 'Set up your company before posting jobs.'
            "
        />

        <Alert v-if="status">
            <AlertDescription>{{ status }}</AlertDescription>
        </Alert>

        <form class="grid gap-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="company_name">Company name</Label>
                <Input
                    id="company_name"
                    v-model="form.company_name"
                    type="text"
                />
                <InputError :message="form.errors.company_name" />
            </div>

            <div class="grid gap-2">
                <Label for="industry">Industry</Label>
                <Input
                    id="industry"
                    v-model="form.industry"
                    type="text"
                    placeholder="e.g. Technology"
                />
                <InputError :message="form.errors.industry" />
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
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
                <Label for="website">Website (optional)</Label>
                <Input
                    id="website"
                    v-model="form.website"
                    type="url"
                    placeholder="https://…"
                />
                <InputError :message="form.errors.website" />
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
