<?php

namespace App\Chat\Database\Factories;

use App\Chat\Enums\MessageType;
use App\Chat\Models\Conversation;
use App\Chat\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'participant_id' => (string) Str::ulid(),
            'type' => MessageType::Text,
            'body' => fake()->sentence(),
            'parent_message_id' => null,
            'edited_at' => null,
        ];
    }

    public function from(string $participantId): static
    {
        return $this->state(fn (array $attributes) => [
            'participant_id' => $participantId,
        ]);
    }

    /**
     * System messages carry no author.
     */
    public function system(?string $body = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MessageType::System,
            'participant_id' => null,
            'body' => $body ?? 'Status changed.',
        ]);
    }
}
