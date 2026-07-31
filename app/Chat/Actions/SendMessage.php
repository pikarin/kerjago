<?php

namespace App\Chat\Actions;

use App\Chat\Enums\MessageType;
use App\Chat\Events\MessageSent;
use App\Chat\Exceptions\NotAParticipant;
use App\Chat\Models\Conversation;
use App\Chat\Models\Message;
use Illuminate\Support\Facades\DB;

class SendMessage
{
    /**
     * Record a participant's message and announce it.
     *
     * @throws NotAParticipant when the author is not in the conversation
     */
    public function handle(
        Conversation $conversation,
        string $participantId,
        string $body,
        ?string $parentMessageId = null,
    ): Message {
        // Authorization proper is the host's job. This guards data integrity:
        // a message from a non-participant would be corrupt however it arrived.
        if (! $conversation->hasParticipant($participantId)) {
            throw NotAParticipant::for($participantId, $conversation->id);
        }

        $message = DB::transaction(function () use ($conversation, $participantId, $body, $parentMessageId): Message {
            $message = $conversation->messages()->create([
                'participant_id' => $participantId,
                'type' => MessageType::Text,
                'body' => $body,
                'parent_message_id' => $parentMessageId,
            ]);

            // create() sets only the foreign key, so Scout's searchable payload
            // would re-fetch the conversation and its participants on the write
            // path. Both are already in memory.
            $message->setRelation('conversation', $conversation);

            $conversation->forceFill(['last_message_at' => $message->created_at])->save();

            return $message;
        });

        // Dispatched after the transaction commits. Broadcasting from inside it
        // can deliver a message that readers cannot yet fetch.
        MessageSent::dispatch($message);

        return $message;
    }
}
