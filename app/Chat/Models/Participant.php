<?php

namespace App\Chat\Models;

use App\Chat\Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $conversation_id
 * @property string $participant_id
 * @property string|null $last_read_message_id
 * @property Carbon|null $last_read_at
 * @property Carbon $joined_at
 * @property Carbon|null $left_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Conversation $conversation
 */
#[Fillable(['participant_id', 'last_read_message_id', 'last_read_at', 'joined_at', 'left_at'])]
class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory, HasUlids;

    protected $table = 'chat_participants';

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    /**
     * @return ParticipantFactory
     */
    protected static function newFactory(): Factory
    {
        return ParticipantFactory::new();
    }
}
