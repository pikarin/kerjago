<script setup lang="ts">
import { Lock } from '@lucide/vue';
import type { LockedApplicantTeaser } from '@/types/kerjago';

/**
 * Locked applicants an employer has, rendered from application data rather than
 * from their threads. Deliberately inert: no unread badge, no timestamp, no
 * message preview — each of those is a signal about someone the employer holds
 * no unlock for (ADR 0013).
 */
defineProps<{
    applicants: LockedApplicantTeaser[];
}>();
</script>

<template>
    <div v-if="applicants.length > 0" class="grid gap-1 border-t pt-3">
        <p class="px-2 text-xs font-medium text-muted-foreground">
            Locked applicants
        </p>
        <div
            v-for="applicant in applicants"
            :key="applicant.application_id"
            class="flex items-start gap-2 rounded-md px-2 py-2 text-sm opacity-70"
        >
            <Lock class="mt-0.5 size-3.5 shrink-0 text-muted-foreground" />
            <span class="grid gap-0.5">
                <span class="font-medium">{{ applicant.display_name }}</span>
                <span class="text-xs text-muted-foreground">
                    {{ applicant.current_title }} · {{ applicant.job_title }}
                </span>
                <span class="text-xs text-muted-foreground">
                    Unlock this candidate to open the conversation.
                </span>
            </span>
        </div>
    </div>
</template>
