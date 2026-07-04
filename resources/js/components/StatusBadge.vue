<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { ApplicationStatus, JobStatus } from '@/types/kerjago';

const props = defineProps<{
    status: JobStatus | ApplicationStatus;
}>();

const styles: Record<string, string> = {
    draft: 'border-transparent bg-muted text-muted-foreground',
    active: 'border-transparent bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    closed: 'border-transparent bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
    submitted:
        'border-transparent bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
    reviewed:
        'border-transparent bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    shortlisted:
        'border-transparent bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    rejected:
        'border-transparent bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
};

const label = computed(
    () => props.status.charAt(0).toUpperCase() + props.status.slice(1),
);
</script>

<template>
    <Badge variant="outline" :class="styles[status]">{{ label }}</Badge>
</template>
