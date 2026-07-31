<?php

namespace App\Chat\Events;

use App\Chat\Data\MessagePayload;
use App\Chat\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
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
