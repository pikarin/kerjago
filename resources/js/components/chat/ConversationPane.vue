<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import MessageComposer from '@/components/chat/MessageComposer.vue';
import MessageList from '@/components/chat/MessageList.vue';
import TypingIndicator from '@/components/chat/TypingIndicator.vue';
import { useConversation } from '@/composables/useConversation';
import { read as markRead } from '@/routes/chat';
import type { ChatConversation, ChatMessage } from '@/types/chat';

const props = defineProps<{
    conversation: ChatConversation;
    initialMessages: ChatMessage[];
    viewerId: string;
    viewerName: string;
}>();

let markingRead = false;

/**
 * A message arrived while this conversation is open, so it has already been
 * seen. Only the sidebar badges need refreshing, hence the partial reload —
 * a full visit would rebuild the message list underneath the user.
 */
function acknowledgeRead(): void {
    if (markingRead) {
        return;
    }

    markingRead = true;

    router.post(
        markRead(props.conversation.id).url,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            only: ['conversations'],
            onFinish: () => {
                markingRead = false;
            },
        },
    );
}

const { messages, onlineIds, typingNames, notifyTyping } = useConversation({
    conversationId: props.conversation.id,
    viewerId: props.viewerId,
    viewerName: props.viewerName,
    initialMessages: props.initialMessages,
    onIncoming: acknowledgeRead,
});

const counterparts = computed(() =>
    props.conversation.participants.filter(
        (participant) => !participant.is_viewer,
    ),
);

const title = computed<string>(() => {
    const names = counterparts.value.map((participant) => participant.name);

    return names.length > 0 ? names.join(', ') : 'Conversation';
});

const anyoneElseHere = computed<boolean>(() =>
    counterparts.value.some((participant) =>
        onlineIds.value.includes(participant.id),
    ),
);
</script>

<template>
    <section class="flex min-w-0 flex-1 flex-col">
        <header class="flex items-start justify-between gap-4 border-b pb-2">
            <div class="min-w-0">
                <h2 class="flex items-center gap-2 truncate font-semibold">
                    {{ title }}
                    <span
                        v-if="anyoneElseHere"
                        class="size-2 shrink-0 rounded-full bg-green-500"
                        title="Online now"
                    />
                </h2>

                <p
                    v-if="conversation.context"
                    class="truncate text-sm text-muted-foreground"
                >
                    <Link
                        v-if="
                            conversation.context.url &&
                            !conversation.context.unavailable
                        "
                        :href="conversation.context.url"
                        class="hover:text-primary"
                    >
                        {{ conversation.context.label }}
                    </Link>
                    <span v-else class="italic">
                        {{ conversation.context.label }}
                    </span>
                </p>
            </div>
        </header>

        <MessageList
            :messages="messages"
            :participants="conversation.participants"
            :viewer-id="viewerId"
        />

        <TypingIndicator :names="typingNames" />

        <MessageComposer
            :conversation-id="conversation.id"
            @typing="notifyTyping"
        />
    </section>
</template>
