<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import type { ChatMessage, ChatParticipant } from '@/types/chat';

const props = defineProps<{
    messages: ChatMessage[];
    participants: ChatParticipant[];
    viewerId: string;
}>();

const scroller = ref<HTMLElement | null>(null);

/**
 * Names come from the conversation's participant list, resolved once per
 * request. Messages themselves carry only participant_id, which is what keeps
 * the HTTP and socket payloads identical.
 */
const participantsById = computed<Record<string, ChatParticipant>>(() =>
    Object.fromEntries(
        props.participants.map((participant) => [participant.id, participant]),
    ),
);

function authorName(message: ChatMessage): string {
    if (message.participant_id === null) {
        return '';
    }

    return participantsById.value[message.participant_id]?.name ?? 'Unknown';
}

function isMine(message: ChatMessage): boolean {
    return message.participant_id === props.viewerId;
}

function timeLabel(iso: string | null): string {
    if (!iso) {
        return '';
    }

    return new Date(iso).toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
    });
}

watch(
    () => props.messages.length,
    async () => {
        await nextTick();

        if (scroller.value) {
            scroller.value.scrollTop = scroller.value.scrollHeight;
        }
    },
    { immediate: true },
);
</script>

<template>
    <div ref="scroller" class="flex-1 space-y-3 overflow-y-auto py-2">
        <p
            v-if="props.messages.length === 0"
            class="py-8 text-center text-sm text-muted-foreground"
        >
            No messages yet. Say hello.
        </p>

        <template v-for="message in props.messages" :key="message.id">
            <p
                v-if="message.type === 'system'"
                class="text-center text-xs text-muted-foreground"
            >
                {{ message.body }}
            </p>

            <div
                v-else
                class="flex flex-col gap-0.5"
                :class="isMine(message) ? 'items-end' : 'items-start'"
            >
                <span
                    v-if="!isMine(message)"
                    class="text-xs font-medium text-muted-foreground"
                >
                    {{ authorName(message) }}
                </span>

                <div
                    class="max-w-[75%] rounded-2xl px-3 py-2 text-sm whitespace-pre-line"
                    :class="
                        isMine(message)
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted'
                    "
                >
                    {{ message.body }}
                </div>

                <span class="text-[10px] text-muted-foreground">
                    {{ timeLabel(message.created_at) }}
                    <span v-if="message.edited_at">· edited</span>
                </span>
            </div>
        </template>
    </div>
</template>
