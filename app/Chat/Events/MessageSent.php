<?php

namespace App\Chat\Events;

use App\Chat\Data\MessagePayload;
use App\Chat\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ShouldDispatchAfterCommit is hardening rather than a fix. SendMessage already
 * dispatches outside its own transaction, but DB::transaction nests as a
 * savepoint: the moment any caller wraps that Action in an outer transaction,
 * the inner commit commits nothing and the broadcast worker would look for a
 * message no reader can fetch. This makes that impossible instead of relying on
 * the call site staying correct.
 */
class MessageSent implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    /**
     * A presence channel, so the client also gets who is currently viewing
     * without any extra server work.
     *
     * @return list<PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [new PresenceChannel('chat.conversations.'.$this->message->conversation_id)];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['message' => MessagePayload::fromMessage($this->message)->toArray()];
    }
}
