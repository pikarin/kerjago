<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BadgeCheck, ShieldQuestionMark } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useCapabilities } from '@/composables/useCapabilities';
import { request } from '@/routes/employer/verification';

/**
 * The company's verification standing, and the way to ask for it.
 *
 * One of the two places allowed to read verification state directly rather than
 * asking the capability map — this banner is *about* verification, not about
 * any one feature it happens to gate.
 */
const { verification } = useCapabilities();

const requestedAt = computed(() => {
    const raw = verification.value?.requested_at;

    return raw === null || raw === undefined ? null : new Date(raw);
});

const waitingSince = computed(() =>
    requestedAt.value === null
        ? null
        : requestedAt.value.toLocaleDateString(undefined, {
              day: 'numeric',
              month: 'long',
          }),
);
</script>

<template>
    <div
        v-if="verification !== null && !verification.verified"
        class="flex flex-wrap items-center gap-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200"
    >
        <ShieldQuestionMark class="size-5 shrink-0" />

        <div class="grid flex-1 gap-1">
            <p class="text-sm font-semibold">Your company isn't verified yet</p>
            <p class="text-sm">
                You can write job ads now, but they stay on hold until we've
                checked your company. Talent Search and messaging open up at the
                same time.
            </p>
            <!--
                "In the queue" rather than "we have your request": a company
                that changed its name after being verified is queued for
                re-review without ever having asked.
            -->
            <p v-if="waitingSince !== null" class="text-sm">
                You've been in the review queue since {{ waitingSince }}.
            </p>
        </div>

        <Button v-if="requestedAt === null" as-child size="sm">
            <Link :href="request()" method="post" as="button">
                Request verification
            </Link>
        </Button>
    </div>

    <div
        v-else-if="verification !== null"
        class="flex items-center gap-2 text-sm text-emerald-700 dark:text-emerald-400"
    >
        <BadgeCheck class="size-4" />
        Verified company
    </div>
</template>
