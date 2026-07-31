<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { MessagesSquare } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { show as conversationShow, index as chatIndex } from '@/routes/chat';
import { store as sendMessage } from '@/routes/chat/messages';
import type { ChatConversation, ChatMessage } from '@/types/chat';
import type { Paginated } from '@/types/kerjago';

const props = defineProps<{
    conversations: Paginated<ChatConversation>;
    conversation: ChatConversation | null;
    messages: Paginated<ChatMessage> | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Chat', href: chatIndex() },
        ],
    },
});

/** Messages arrive newest-first for pagination; display oldest-first. */
const orderedMessages = computed<ChatMessage[]>(() =>
    props.messages ? [...props.messages.data].reverse() : [],
);

const participantNames = computed<Record<string, string>>(() =>
    Object.fromEntries(
        (props.conversation?.participants ?? []).map((participant) => [
            participant.id,
            participant.name,
        ]),
    ),
);

function counterparts(conversation: ChatConversation): string {
    return conversation.participants
        .filter((participant) => !participant.is_viewer)
        .map((participant) => participant.name)
        .join(', ');
}

const form = useForm({ body: '' });

function submit(): void {
    if (!props.conversation) {
        return;
    }

    form.post(sendMessage(props.conversation.id).url, {
        preserveScroll: true,
        onSuccess: () => form.reset('body'),
    });
}
</script>

<template>
    <Head title="Chat" />

    <div class="flex h-full flex-1 gap-4 p-4">
        <aside class="w-72 shrink-0 space-y-1 overflow-y-auto border-r pr-4">
            <h1 class="mb-2 text-sm font-medium text-muted-foreground">
                Conversations
            </h1>

            <p
                v-if="conversations.data.length === 0"
                class="text-sm text-muted-foreground"
            >
                No conversations yet.
            </p>

            <Link
                v-for="item in conversations.data"
                :key="item.id"
                :href="conversationShow(item.id)"
                class="block rounded-lg px-3 py-2 text-sm hover:bg-muted"
                :class="{ 'bg-muted': item.id === conversation?.id }"
            >
                <span class="flex items-center justify-between gap-2">
                    <span class="truncate font-medium">
                        {{ counterparts(item) || 'Conversation' }}
                    </span>
                    <span
                        v-if="item.unread_count > 0"
                        class="rounded-full bg-primary px-1.5 text-xs text-primary-foreground"
                    >
                        {{ item.unread_count }}
                    </span>
                </span>
                <span
                    v-if="item.context"
                    class="block truncate text-xs text-muted-foreground"
                >
                    {{ item.context.label }}
                </span>
            </Link>
        </aside>

        <section class="flex min-w-0 flex-1 flex-col">
            <EmptyState
                v-if="!conversation"
                :icon="MessagesSquare"
                title="Pick a conversation"
                description="Applications, outreach, and messages from the Kerjago team all arrive here."
            />

            <template v-else>
                <header class="mb-3 border-b pb-2">
                    <h2 class="font-semibold">
                        {{ counterparts(conversation) || 'Conversation' }}
                    </h2>
                    <p
                        v-if="conversation.context"
                        class="text-sm text-muted-foreground"
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
                        <span v-else>{{ conversation.context.label }}</span>
                    </p>
                </header>

                <ul class="flex-1 space-y-3 overflow-y-auto">
                    <li
                        v-for="message in orderedMessages"
                        :key="message.id"
                        class="text-sm"
                    >
                        <p
                            v-if="message.type === 'system'"
                            class="text-center text-xs text-muted-foreground"
                        >
                            {{ message.body }}
                        </p>
                        <template v-else>
                            <span
                                class="text-xs font-medium text-muted-foreground"
                            >
                                {{
                                    participantNames[
                                        message.participant_id ?? ''
                                    ] ?? 'Unknown'
                                }}
                            </span>
                            <p class="whitespace-pre-line">{{ message.body }}</p>
                        </template>
                    </li>
                </ul>

                <form class="mt-3 flex gap-2 border-t pt-3" @submit.prevent="submit">
                    <input
                        v-model="form.body"
                        type="text"
                        class="flex-1 rounded-md border px-3 py-2 text-sm"
                        placeholder="Write a message"
                        :disabled="form.processing"
                    />
                    <Button type="submit" :disabled="form.processing || !form.body">
                        Send
                    </Button>
                </form>
            </template>
        </section>
    </div>
</template>
