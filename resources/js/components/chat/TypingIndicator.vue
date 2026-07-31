<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    names: string[];
}>();

const label = computed<string>(() => {
    if (props.names.length === 0) {
        return '';
    }

    if (props.names.length === 1) {
        return `${props.names[0]} is typing`;
    }

    if (props.names.length === 2) {
        return `${props.names[0]} and ${props.names[1]} are typing`;
    }

    return 'Several people are typing';
});
</script>

<template>
    <p
        class="flex h-5 items-center gap-1 text-xs text-muted-foreground"
        aria-live="polite"
    >
        <template v-if="props.names.length > 0">
            <span class="flex gap-0.5">
                <span class="size-1 animate-bounce rounded-full bg-current" />
                <span
                    class="size-1 animate-bounce rounded-full bg-current"
                    style="animation-delay: 0.15s"
                />
                <span
                    class="size-1 animate-bounce rounded-full bg-current"
                    style="animation-delay: 0.3s"
                />
            </span>
            {{ label }}
        </template>
    </p>
</template>
