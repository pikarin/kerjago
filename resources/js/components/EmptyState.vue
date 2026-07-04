<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

defineProps<{
    icon: LucideIcon;
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed p-10 text-center"
    >
        <component :is="icon" class="size-8 text-muted-foreground" />
        <h3 class="font-medium">{{ title }}</h3>
        <p v-if="description" class="max-w-sm text-sm text-muted-foreground">
            {{ description }}
        </p>
        <div v-if="$slots.default" class="mt-2">
            <slot />
        </div>
    </div>
</template>
