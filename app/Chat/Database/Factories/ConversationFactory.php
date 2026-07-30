<?php

namespace App\Chat\Database\Factories;

use App\Chat\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    /**
     * `kind` is an arbitrary string here on purpose. The module has no opinion
     * about which kinds exist; the host supplies them.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => 'direct',
            'context_type' => null,
            'context_id' => null,
            'unique_key' => null,
            'meta' => null,
            'created_by_participant_id' => (string) Str::ulid(),
            'last_message_at' => null,
        ];
    }

    /**
     * Bind the conversation to an arbitrary host record.
     */
    public function boundTo(string $type, string $id, bool $unique = false): static
    {
        return $this->state(fn (array $attributes) => [
            'context_type' => $type,
            'context_id' => $id,
            'unique_key' => $unique ? "{$type}:{$id}" : null,
        ]);
    }

    public function kind(string $kind): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => $kind,
        ]);
    }
}
