<script setup lang="ts">
import { Lock } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';

/**
 * Marks a candidate whose name, email and phone arrive masked. The values are
 * masked server-side, so this badge explains what the employer is seeing rather
 * than hiding anything itself (ADR 0013).
 */
withDefaults(defineProps<{ label?: string }>(), { label: 'Locked' });
</script>

<template>
    <!-- The hook is for browser tests: "Locked" is a substring of "unlocked",
         which the applicant list says in its quota line, so the badge cannot be
         asserted absent by its own text. -->
    <Badge
        variant="outline"
        data-test="locked-badge"
        class="gap-1 border-transparent bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"
    >
        <Lock class="size-3" />
        {{ label }}
    </Badge>
</template>
