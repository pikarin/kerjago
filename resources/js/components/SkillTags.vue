<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

withDefaults(
    defineProps<{
        skills: string[];
        limit?: number;
    }>(),
    { limit: 0 },
);
</script>

<template>
    <div class="flex flex-wrap gap-1.5">
        <Badge
            v-for="skill in limit > 0 ? skills.slice(0, limit) : skills"
            :key="skill"
            variant="secondary"
        >
            {{ skill }}
        </Badge>
        <Badge v-if="limit > 0 && skills.length > limit" variant="outline">
            +{{ skills.length - limit }}
        </Badge>
    </div>
</template>
