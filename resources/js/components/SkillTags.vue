<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

withDefaults(
    defineProps<{
        skills: string[];
        limit?: number;
        // Tints the whole set — talent search marks skills as a matched
        // field per hit, not per individual skill.
        highlighted?: boolean;
    }>(),
    { limit: 0, highlighted: false },
);
</script>

<template>
    <div class="flex flex-wrap gap-1.5">
        <Badge
            v-for="skill in limit > 0 ? skills.slice(0, limit) : skills"
            :key="skill"
            variant="secondary"
            :class="highlighted ? 'font-semibold text-primary' : ''"
        >
            {{ skill }}
        </Badge>
        <Badge v-if="limit > 0 && skills.length > limit" variant="outline">
            +{{ skills.length - limit }}
        </Badge>
    </div>
</template>
