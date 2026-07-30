<?php

namespace App\Chat\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $message_id
 * @property string $participant_id
 * @property string $emoji
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Message $message
 */
#[Fillable(['participant_id', 'emoji'])]
class MessageReaction extends Model
{
    use HasUlids;

    protected $table = 'chat_message_reactions';

    /**
     * @return BelongsTo<Message, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
