<script setup lang="ts">
import { computed } from 'vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import type { FacetCount, FacetOption } from '@/types/kerjago';

const props = defineProps<{
    title: string;
    options: FacetOption[];
    counts?: FacetCount[];
}>();

const model = defineModel<string[]>({ required: true });

// Counts are undefined when the search engine is unavailable (DB fallback);
// checkboxes still filter, they just render without counts.
const countFor = computed(() => {
    if (!props.counts) {
        return undefined;
    }

    const map = new Map(props.counts.map((c) => [c.value, c.count]));

    return (value: string): number => map.get(value) ?? 0;
});

function toggle(value: string, checked: boolean): void {
    model.value = checked
        ? [...model.value, value]
        : model.value.filter((v) => v !== value);
}
</script>

<template>
    <fieldset class="grid gap-2">
        <legend class="mb-2 text-sm font-medium">{{ title }}</legend>
        <div
            v-for="option in options"
            :key="option.value"
            class="flex items-center gap-2"
        >
            <Checkbox
                :id="`facet-${title}-${option.value}`"
                :model-value="model.includes(option.value)"
                @update:model-value="
                    (checked) => toggle(option.value, checked === true)
                "
            />
            <Label
                :for="`facet-${title}-${option.value}`"
                class="flex-1 cursor-pointer text-sm font-normal"
            >
                {{ option.label }}
                <span v-if="countFor" class="text-muted-foreground">
                    ({{ countFor(option.value) }})
                </span>
            </Label>
        </div>
    </fieldset>
</template>
