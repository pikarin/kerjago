<?php

namespace App\Chat\Actions;

use App\Chat\Enums\MessageType;
use App\Chat\Events\MessageSent;
use App\Chat\Models\Conversation;
use App\Chat\Models\Message;
use Illuminate\Support\Facades\DB;

class PostSystemMessage
{
    /**
     * Record a message the host application emits rather than a person —
     * an application status change, for instance.
     *
     * No membership check: the author is the system, which is in every
     * conversation by definition.
     */
    public function handle(Conversation $conversation, string $body): Message
    {
        $message = DB::transaction(function () use ($conversation, $body): Message {
            $message = $conversation->messages()->create([
                'participant_id' => null,
                'type' => MessageType::System,
                'body' => $body,
            ]);

            $conversation->forceFill(['last_message_at' => $message->created_at])->save();

            return $message;
        });

        MessageSent::dispatch($message);

        return $message;
    }
}
