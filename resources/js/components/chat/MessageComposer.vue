<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { SendHorizontal } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { store as sendMessage } from '@/routes/chat/messages';

const props = defineProps<{
    conversationId: string;
}>();

const emit = defineEmits<{
    typing: [];
}>();

const form = useForm({ body: '' });

function submit(): void {
    if (form.body.trim() === '') {
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
    <form class="border-t pt-3" @submit.prevent="submit">
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
