export type ConversationKind = 'application' | 'cold_outreach' | 'internal';

export type MessageType = 'text' | 'system';

export type ChatContext = {
    type: string;
    label: string;
    url: string | null;
    /** The host record is gone — a deleted job, say. Chat keeps no foreign key. */
    unavailable: boolean;
};

export type ChatParticipant = {
    id: string;
    name: string;
    avatar_url: string | null;
    unavailable: boolean;
    is_viewer: boolean;
    last_read_at: string | null;
    left_at: string | null;
};

export type ChatConversation = {
    id: string;
    kind: ConversationKind;
    context: ChatContext | null;
    participants: ChatParticipant[];
    unread_count: number;
    last_message_at: string | null;
};

export type ChatReaction = {
    emoji: string;
    count: number;
    participant_ids: string[];
};

/**
 * Carries participant_id and no author name. Names are looked up from the
 * conversation's participant list, which is resolved once per request — so this
 * shape is identical whether it arrived over HTTP or the socket.
 */
/**
 * A bounded list, not a paginator. `truncated` says the cap was hit, so a
 * partial list is never presented as a complete one.
 */
export type ChatSearchResults = {
    data: ChatMessage[];
    truncated: boolean;
};

export type ChatMessage = {
    id: string;
    conversation_id: string;
    participant_id: string | null;
    type: MessageType;
    body: string | null;
    parent_message_id: string | null;
    edited_at: string | null;
    created_at: string | null;
    reactions: ChatReaction[];
};
