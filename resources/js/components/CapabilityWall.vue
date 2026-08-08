<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Lock } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useCapabilities } from '@/composables/useCapabilities';
import { request } from '@/routes/employer/verification';
import type { CapabilityDenialReason } from '@/types/capabilities';

const props = defineProps<{
    reason: CapabilityDenialReason | null;
    /** What the employer is being shown the edge of, e.g. "candidates". */
    subject?: string;
}>();

const { verification } = useCapabilities();

/**
 * Blurred placeholder cards behind the message. Deliberately fake and
 * deliberately unreadable — they show that the list continues without
 * suggesting how far, which is the one thing an unverified account should not
 * be able to read off the page.
 */
const placeholders = [0, 1, 2, 3, 4, 5];

// `?? null` rather than a bare `!== null`: a viewer with no verification prop
// at all yields undefined, and undefined is not null — the call to action would
// be replaced by "your request is with our team" for someone who never asked.
const alreadyRequested = computed(
    () => (verification.value?.requested_at ?? null) !== null,
);

const copy = computed(() => ({
    title: `There are more ${props.subject ?? 'results'} here`,
    body:
        props.reason === 'verification_required'
            ? 'Verify your company to search the full list, see who matches your filters, and message candidates directly.'
            : 'Your account cannot see the full list yet.',
}));
</script>

<template>
    <div class="relative overflow-hidden rounded-xl border bg-card">
        <div
            class="grid gap-4 p-4 blur-sm select-none sm:grid-cols-2 xl:grid-cols-3"
            aria-hidden="true"
        >
            <Card v-for="placeholder in placeholders" :key="placeholder">
                <CardContent class="grid gap-3 py-6">
                    <div class="h-4 w-2/3 rounded bg-muted" />
                    <div class="h-3 w-1/2 rounded bg-muted" />
                    <div class="flex gap-2">
                        <div class="h-5 w-16 rounded-full bg-muted" />
                        <div class="h-5 w-12 rounded-full bg-muted" />
                    </div>
                </CardContent>
            </Card>
        </div>

        <div
            class="absolute inset-0 grid place-items-center bg-gradient-to-b from-card/60 to-card p-6 text-center"
        >
            <div class="grid max-w-md justify-items-center gap-3">
                <span class="rounded-full bg-primary/10 p-3 text-primary">
                    <Lock class="size-5" />
                </span>
                <h3 class="text-lg font-semibold">{{ copy.title }}</h3>
                <p class="text-sm text-muted-foreground">{{ copy.body }}</p>

                <p v-if="alreadyRequested" class="text-sm font-medium">
                    Your verification request is with our team.
                </p>
                <Button v-else as-child>
                    <Link :href="request()" method="post" as="button">
                        Get verified
                    </Link>
                </Button>
            </div>
        </div>
    </div>
</template>
