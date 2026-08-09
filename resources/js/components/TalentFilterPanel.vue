<script setup lang="ts">
import { ChevronsUpDown } from '@lucide/vue';
import { ref } from 'vue';
import FacetGroup from '@/components/FacetGroup.vue';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import type { FacetOption, Facets, TalentFilterForm } from '@/types/kerjago';

/**
 * The talent search filter set, rendered in the desktop sidebar and inside
 * the mobile sheet. Every group is fixed-option; no engine-derived facets.
 */
const props = defineProps<{
    facets: Facets;
    facetsAvailable: boolean;
    facetOptions: {
        experience_band: FacetOption[];
        availability: FacetOption[];
        country: FacetOption[];
        preferred_country: FacetOption[];
        languages: FacetOption[];
        education_level: FacetOption[];
        gender: FacetOption[];
    };
    hasFilters: boolean;
}>();

const emit = defineEmits<{ clear: [] }>();

const model = defineModel<TalentFilterForm>({ required: true });

// The secondary groups start open when one of them is already narrowing.
const moreFiltersOpen = ref(
    model.value.preferred_country.length > 0 ||
        model.value.languages.length > 0 ||
        model.value.education_level.length > 0 ||
        model.value.gender.length > 0,
);

function countsFor(facet: string) {
    return props.facetsAvailable ? (props.facets[facet] ?? []) : undefined;
}
</script>

<template>
    <div class="grid gap-6">
        <FacetGroup
            v-model="model.experience_band"
            title="Experience"
            :options="facetOptions.experience_band"
            :counts="countsFor('experience_band')"
        />
        <FacetGroup
            v-model="model.availability"
            title="Availability"
            :options="facetOptions.availability"
            :counts="countsFor('availability')"
        />
        <FacetGroup
            v-model="model.country"
            title="Country"
            :options="facetOptions.country"
            :counts="countsFor('country')"
        />

        <div class="grid gap-2">
            <span class="text-sm font-medium">Minimum experience</span>
            <Input
                v-model.number="model.experience_min"
                type="number"
                min="0"
                placeholder="Min years exp."
            />
        </div>

        <Collapsible v-model:open="moreFiltersOpen" class="grid gap-6">
            <CollapsibleTrigger as-child>
                <Button variant="ghost" size="sm" class="justify-self-start">
                    <ChevronsUpDown class="size-4" />
                    More filters
                </Button>
            </CollapsibleTrigger>
            <CollapsibleContent class="grid gap-6">
                <FacetGroup
                    v-model="model.preferred_country"
                    title="Preferred country"
                    :options="facetOptions.preferred_country"
                    :counts="countsFor('preferred_country')"
                />
                <FacetGroup
                    v-model="model.languages"
                    title="Languages"
                    :options="facetOptions.languages"
                    :counts="countsFor('languages')"
                />
                <FacetGroup
                    v-model="model.education_level"
                    title="Education"
                    :options="facetOptions.education_level"
                    :counts="countsFor('education_level')"
                />
                <FacetGroup
                    v-model="model.gender"
                    title="Gender"
                    :options="facetOptions.gender"
                    :counts="countsFor('gender')"
                />
            </CollapsibleContent>
        </Collapsible>

        <Button
            v-if="hasFilters"
            variant="ghost"
            size="sm"
            class="justify-self-start"
            @click="emit('clear')"
        >
            Clear filters
        </Button>
    </div>
</template>
