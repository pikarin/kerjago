<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { show as conversationShow } from '@/routes/chat';
import type { ChatConversation } from '@/types/chat';

const props = defineProps<{
    conversations: ChatConversation[];
    activeId: string | null;
}>();

/** Everyone except the viewer — who the conversation is *with*. */
function counterparts(conversation: ChatConversation): string {
    const names = conversation.participants
        .filter((participant) => !participant.is_viewer)
        .map((participant) => participant.name);

    return names.length > 0 ? names.join(', ') : 'Conversation';
}

const kindLabels: Record<ChatConversation['kind'], string> = {
    application: 'Application',
    cold_outreach: 'Outreach',
    internal: 'Kerjago',
};
</script>

<template>
    <nav class="space-y-1">
        <p
            v-if="props.conversations.length === 0"
            class="px-3 py-2 text-sm text-muted-foreground"
        >
            No conversations yet.
        </p>

        <Link
            v-for="conversation in props.conversations"
            :key="conversation.id"
            :href="conversationShow(conversation.id)"
            prefetch
            class="block rounded-lg px-3 py-2 transition-colors hover:bg-muted"
            :class="{ 'bg-muted': conversation.id === props.activeId }"
        >
            <span class="flex items-center justify-between gap-2">
                <span class="truncate text-sm font-medium">
                    {{ counterparts(conversation) }}
                </span>
                <span
                    v-if="conversation.unread_count > 0"
                    class="shrink-0 rounded-full bg-primary px-1.5 py-0.5 text-xs font-medium text-primary-foreground"
                >
                    {{ conversation.unread_count }}
                </span>
            </span>

            <span
                class="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground"
            >
                <span class="rounded bg-muted-foreground/10 px-1">
                    {{ kindLabels[conversation.kind] }}
                </span>
                <span v-if="conversation.context" class="truncate">
                    {{ conversation.context.label }}
                </span>
            </span>
        </Link>
    </nav>
</template>
