<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { MessagesSquare } from '@lucide/vue';
import { computed } from 'vue';
import ConversationList from '@/components/chat/ConversationList.vue';
import ConversationPane from '@/components/chat/ConversationPane.vue';
import MessageSearch from '@/components/chat/MessageSearch.vue';
import EmptyState from '@/components/EmptyState.vue';
import { dashboard } from '@/routes';
import { index as chatIndex } from '@/routes/chat';
import type { User } from '@/types';
import type {
    ChatConversation,
    ChatMessage,
    ChatSearchResults,
} from '@/types/chat';
import type { Paginated } from '@/types/kerjago';

const props = defineProps<{
    conversations: Paginated<ChatConversation>;
    conversation: ChatConversation | null;
    messages: Paginated<ChatMessage> | null;
    searchQuery: string;
    searchResults: ChatSearchResults | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Chat', href: chatIndex() },
        ],
    },
});

const page = usePage();
const viewer = computed(() => page.props.auth.user as User);

/** History arrives newest-first for pagination; display oldest-first. */
const initialMessages = computed<ChatMessage[]>(() =>
    props.messages ? [...props.messages.data].reverse() : [],
);

/**
 * The viewer's *resolved* display name, not auth.user.name — an employer is
 * shown as their company everywhere else in chat, so a typing indicator must
 * not reveal the personal account name behind it.
 */
const viewerName = computed<string>(
    () =>
        props.conversation?.participants.find(
            (participant) => participant.is_viewer,
        )?.name ?? '',
);
</script>

<template>
    <Head title="Chat" />

    <div class="flex h-full flex-1 gap-4 overflow-hidden p-4">
        <aside class="flex w-72 shrink-0 flex-col gap-3 border-r pr-4">
            <MessageSearch :query="searchQuery" :results="searchResults" />

            <div class="flex-1 overflow-y-auto">
                <!-- Search replaces the list rather than sitting beside it, so
                     there is never a question of which pane a result is in. -->
                <ConversationList
                    v-if="!searchResults"
                    :conversations="conversations.data"
                    :active-id="conversation?.id ?? null"
                />
            </div>
        </aside>

        <EmptyState
            v-if="!conversation"
            :icon="MessagesSquare"
            title="Pick a conversation"
            description="Applications, outreach, and messages from the Kerjago team all arrive here."
            class="flex-1"
        />

        <!--
            Keyed by conversation id so the pane remounts when the user switches
            conversations. The presence channel name is fixed at mount, so
            without the key it would keep listening to the previous conversation.
        -->
        <ConversationPane
            v-else
            :key="conversation.id"
            :conversation="conversation"
            :initial-messages="initialMessages"
            :viewer-id="viewer.id"
            :viewer-name="viewerName"
        />
    </div>
</template>
