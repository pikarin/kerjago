<?php

namespace App\Chat\Events;

use App\Chat\Models\Participant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Participant $participant) {}

    /**
     * @return list<PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [new PresenceChannel('chat.conversations.'.$this->participant->conversation_id)];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'participant_id' => $this->participant->participant_id,
            'conversation_id' => $this->participant->conversation_id,
            'last_read_message_id' => $this->participant->last_read_message_id,
            'last_read_at' => $this->participant->last_read_at?->toIso8601String(),
        ];
    }
}
