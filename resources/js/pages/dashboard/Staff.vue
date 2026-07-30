<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { LifeBuoy } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <h1 class="text-xl font-semibold tracking-tight">Internal</h1>

        <EmptyState
            :icon="LifeBuoy"
            title="No internal tools yet"
            description="Staff accounts exist so the Kerjago team can join conversations with jobseekers and employers. The shared inbox lands here."
        />
    </div>
</template>
