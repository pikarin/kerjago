<?php

namespace App\Chat\Models;

use App\Chat\Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $kind
 * @property string|null $context_type
 * @property string|null $context_id
 * @property string|null $unique_key
 * @property array<string, mixed>|null $meta
 * @property string $created_by_participant_id
 * @property Carbon|null $last_message_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $unread_count Present only when loaded via a withCount, as ListConversations does.
 * @property-read Collection<int, Participant> $participants
 * @property-read Collection<int, Message> $messages
 */
#[Fillable(['kind', 'context_type', 'context_id', 'unique_key', 'meta', 'created_by_participant_id', 'last_message_at'])]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory, HasUlids;

    protected $table = 'chat_conversations';

    /**
     * @return HasMany<Participant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Membership is the baseline authorization rule. Anything beyond it is a
     * host policy question — see App\Chat\Contracts\ChatAuthorizer.
     *
     * Someone who has left is no longer a member for access purposes, but their
     * row and their messages are retained.
     */
    public function hasParticipant(string $participantId): bool
    {
        if ($this->relationLoaded('participants')) {
            return $this->participants->contains(
                fn (Participant $participant) => $participant->participant_id === $participantId
                    && $participant->left_at === null,
            );
        }

        // A caller that has not eager-loaded gets a bounded EXISTS rather than a
        // lazy load that hydrates every participant row. Route-bound models
        // arrive unloaded, and this method is what the policy and the broadcast
        // channel both authorize on.
        return $this->participants()
            ->where('participant_id', $participantId)
            ->whereNull('left_at')
            ->exists();
    }

    /**
     * @return list<string>
     */
    public function participantIds(): array
    {
        $this->loadMissing('participants');

        return array_values(
            $this->participants
                ->map(fn (Participant $participant): string => $participant->participant_id)
                ->all(),
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * Declared explicitly: the module keeps its factories inside itself so the
     * directory can be lifted out whole, which puts them outside the
     * Database\Factories namespace Laravel would otherwise guess.
     *
     * @return ConversationFactory
     */
    protected static function newFactory(): Factory
    {
        return ConversationFactory::new();
    }
}
