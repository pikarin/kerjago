import { useEchoPresence } from '@laravel/echo-vue';
import type { Ref } from 'vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import type { ChatMessage } from '@/types/chat';

type MessageSentPayload = { message: ChatMessage };

type MessageReadPayload = {
    participant_id: string;
    conversation_id: string;
    last_read_message_id: string | null;
    last_read_at: string | null;
};

type ChannelPayload = MessageSentPayload | MessageReadPayload;

type PresenceUser = { id: string; name: string };

type TypingPayload = { id: string; name: string };

/**
 * The subset of Echo's presence channel this composable uses. Typed locally so
 * the callbacks stay checked rather than falling back to `any`.
 */
type PresenceChannelLike = {
    here: (callback: (users: PresenceUser[]) => void) => PresenceChannelLike;
    joining: (callback: (user: PresenceUser) => void) => PresenceChannelLike;
    leaving: (callback: (user: PresenceUser) => void) => PresenceChannelLike;
    whisper: (event: string, data: unknown) => PresenceChannelLike;
    listenForWhisper: (
        event: string,
        callback: (data: TypingPayload) => void,
    ) => PresenceChannelLike;
};

/** How long a whisper keeps someone in the typing list before it lapses. */
const TYPING_TTL_MS = 3000;

/** Minimum gap between outgoing typing whispers, to avoid one per keystroke. */
const TYPING_THROTTLE_MS = 1500;

export type UseConversation = {
    messages: Ref<ChatMessage[]>;
    onlineIds: Ref<string[]>;
    typingNames: Ref<string[]>;
    readAtByParticipant: Ref<Record<string, string | null>>;
    notifyTyping: () => void;
};

export function useConversation(options: {
    conversationId: string;
    viewerId: string;
    viewerName: string;
    initialMessages: ChatMessage[];
    /** Fired when a message arrives from someone else. */
    onIncoming?: () => void;
}): UseConversation {
    const messages = ref<ChatMessage[]>([...options.initialMessages]);
    const onlineIds = ref<string[]>([]);
    const typingNames = ref<string[]>([]);
    const readAtByParticipant = ref<Record<string, string | null>>({});

    const typingTimers = new Map<string, ReturnType<typeof setTimeout>>();
    let lastWhisperAt = 0;

    /**
     * Upsert rather than push. The sender receives their own broadcast as well
     * as the message in the Inertia response, so appending blindly would show
     * every sent message twice.
     */
    function upsert(message: ChatMessage): void {
        const index = messages.value.findIndex(
            (existing) => existing.id === message.id,
        );

        if (index !== -1) {
            messages.value[index] = message;

            return;
        }

        messages.value.push(message);
        // ULIDs sort chronologically, so ordering by id needs no timestamps.
        messages.value.sort((a, b) => (a.id < b.id ? -1 : 1));
    }

    function markTyping(payload: TypingPayload): void {
        if (payload.id === options.viewerId) {
            return;
        }

        if (!typingNames.value.includes(payload.name)) {
            typingNames.value.push(payload.name);
        }

        const existing = typingTimers.get(payload.id);

        if (existing) {
            clearTimeout(existing);
        }

        typingTimers.set(
            payload.id,
            setTimeout(() => {
                typingNames.value = typingNames.value.filter(
                    (name) => name !== payload.name,
                );
                typingTimers.delete(payload.id);
            }, TYPING_TTL_MS),
        );
    }

    // '.message.sent' with the leading dot: the events declare broadcastAs, so
    // Echo would otherwise look for a class-named event.
    const { channel } = useEchoPresence<ChannelPayload>(
        `chat.conversations.${options.conversationId}`,
        ['.message.sent', '.message.read'],
        (payload) => {
            if ('message' in payload) {
                upsert(payload.message);

                if (payload.message.participant_id !== options.viewerId) {
                    options.onIncoming?.();
                }

                return;
            }

            readAtByParticipant.value[payload.participant_id] =
                payload.last_read_at;
        },
    );

    function presence(): PresenceChannelLike | null {
        const raw = channel();

        return raw ? (raw as unknown as PresenceChannelLike) : null;
    }

    /**
     * Client-to-client, so a typing indicator never touches the server or the
     * database. Throttled so holding a key does not emit a whisper per stroke.
     */
    function notifyTyping(): void {
        const now = Date.now();

        if (now - lastWhisperAt < TYPING_THROTTLE_MS) {
            return;
        }

        lastWhisperAt = now;

        presence()?.whisper('typing', {
            id: options.viewerId,
            name: options.viewerName,
        });
    }

    onMounted(() => {
        const joined = presence();

        if (!joined) {
            return;
        }

        joined.here((users) => {
            onlineIds.value = users.map((user) => user.id);
        });

        joined.joining((user) => {
            if (!onlineIds.value.includes(user.id)) {
                onlineIds.value.push(user.id);
            }
        });

        joined.leaving((user) => {
            onlineIds.value = onlineIds.value.filter((id) => id !== user.id);
        });

        joined.listenForWhisper('typing', markTyping);
    });

    onBeforeUnmount(() => {
        typingTimers.forEach((timer) => clearTimeout(timer));
        typingTimers.clear();
    });

    return {
        messages,
        onlineIds,
        typingNames,
        readAtByParticipant,
        notifyTyping,
    };
}
