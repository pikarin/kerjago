<?php

namespace App\Chat\Database\Factories;

use App\Chat\Models\Conversation;
use App\Chat\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    /**
     * participant_id defaults to a random ULID: the module never assumes host
     * ids exist, and tests that care pass a real user id explicitly.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'participant_id' => (string) Str::ulid(),
            'last_read_message_id' => null,
            'last_read_at' => null,
            'joined_at' => now(),
            'left_at' => null,
        ];
    }

    public function for_(string $participantId): static
    {
        return $this->state(fn (array $attributes) => [
            'participant_id' => $participantId,
        ]);
    }

    public function departed(): static
    {
        return $this->state(fn (array $attributes) => [
            'left_at' => now(),
        ]);
    }
}
