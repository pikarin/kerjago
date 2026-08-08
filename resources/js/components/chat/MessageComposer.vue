<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { SendHorizontal } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useCapabilities } from '@/composables/useCapabilities';
import { store as sendMessage } from '@/routes/chat/messages';

const props = withDefaults(
    defineProps<{
        conversationId: string;
        /**
         * False leaves the thread readable and closes the composer — a company
         * whose standing has lapsed still sees what a candidate wrote, it just
         * cannot reply.
         */
        canSend?: boolean;
    }>(),
    { canSend: true },
);

const emit = defineEmits<{
    typing: [];
}>();

/**
 * Why the composer is closed, taken from the capability rather than assumed.
 *
 * `can_send_message` is a bare boolean, so the reason has to come from the
 * capability map — otherwise the moment a second denial reason exists, an
 * employer held back for some other cause is told to get verified.
 */
const { decision } = useCapabilities();

const deniedMessage = computed(() =>
    decision('participate_in_chat').reason === 'verification_required'
        ? "You can read this conversation, but you can't reply until your company is verified."
        : "You can read this conversation, but you can't reply to it.",
);

const form = useForm({ body: '' });

function submit(): void {
    if (!props.canSend || form.body.trim() === '') {
        return;
    }

    form.post(sendMessage(props.conversationId).url, {
        preserveScroll: true,
        preserveState: true,
        // The list is keyed by message id and upserts, so the message arriving
        // in this response and over the socket cannot render twice.
        onSuccess: () => form.reset('body'),
    });
}
</script>

<template>
    <p
        v-if="!canSend"
        class="mt-3 rounded-md border border-dashed bg-muted/40 px-3 py-2 text-sm text-muted-foreground"
    >
        {{ deniedMessage }}
    </p>

    <form v-else class="border-t pt-3" @submit.prevent="submit">
        <div class="flex items-end gap-2">
            <textarea
                v-model="form.body"
                rows="1"
                placeholder="Write a message"
                class="max-h-32 flex-1 resize-y rounded-md border bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-ring focus:outline-none"
                :disabled="form.processing"
                @input="emit('typing')"
                @keydown.enter.exact.prevent="submit"
            />
            <Button
                type="submit"
                size="icon"
                :disabled="form.processing || form.body.trim() === ''"
                aria-label="Send message"
            >
                <SendHorizontal class="size-4" />
            </Button>
        </div>

        <InputError :message="form.errors.body" class="mt-1" />
    </form>
</template>
