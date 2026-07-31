<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Search, X } from '@lucide/vue';
import { ref, watch } from 'vue';
import { index as chatIndex, show as conversationShow } from '@/routes/chat';
import type { ChatSearchResults } from '@/types/chat';

const props = defineProps<{
    query: string;
    results: ChatSearchResults | null;
}>();

const term = ref(props.query);

// Keep the field in step when the prop changes, so clearing the search or
// arriving from a link does not leave a stale value in the box.
watch(
    () => props.query,
    (value) => {
        term.value = value;
    },
);

function submit(): void {
    const trimmed = term.value.trim();

    router.get(chatIndex().url, trimmed === '' ? {} : { q: trimmed }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function clear(): void {
    term.value = '';
    submit();
}
</script>

<template>
    <div class="space-y-2">
        <form class="relative" @submit.prevent="submit">
            <Search
                class="pointer-events-none absolute top-1/2 left-2 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <input
                v-model="term"
                type="search"
                placeholder="Search messages"
                class="w-full rounded-md border bg-background py-1.5 pr-8 pl-8 text-sm focus:ring-2 focus:ring-ring focus:outline-none"
            />
            <button
                v-if="props.query !== ''"
                type="button"
                class="absolute top-1/2 right-2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                aria-label="Clear search"
                @click="clear"
            >
                <X class="size-4" />
            </button>
        </form>

        <div v-if="props.results" class="space-y-1">
            <p class="px-1 text-xs text-muted-foreground">
                {{ props.results.data.length }}
                {{ props.results.data.length === 1 ? 'match' : 'matches' }}
                <span v-if="props.results.truncated">(first 30)</span>
            </p>

            <p
                v-if="props.results.data.length === 0"
                class="px-3 py-2 text-sm text-muted-foreground"
            >
                Nothing found.
            </p>

            <Link
                v-for="message in props.results.data"
                :key="message.id"
                :href="conversationShow(message.conversation_id)"
                class="block rounded-lg px-3 py-2 text-sm hover:bg-muted"
            >
                <span class="line-clamp-2 text-muted-foreground">
                    {{ message.body }}
                </span>
            </Link>
        </div>
    </div>
</template>
