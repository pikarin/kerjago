<?php

namespace App\Chat\Models;

use App\Chat\Database\Factories\MessageFactory;
use App\Chat\Enums\MessageType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $conversation_id
 * @property string|null $participant_id
 * @property MessageType $type
 * @property string|null $body
 * @property string|null $parent_message_id
 * @property Carbon|null $edited_at
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Conversation $conversation
 * @property-read Message|null $parent
 * @property-read Collection<int, MessageAttachment> $attachments
 * @property-read Collection<int, MessageReaction> $reactions
 */
#[Fillable(['participant_id', 'type', 'body', 'parent_message_id', 'edited_at'])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'chat_messages';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'text',
    ];

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return BelongsTo<Message, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_message_id');
    }

    /**
     * @return HasMany<MessageAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    /**
     * @return HasMany<MessageReaction, $this>
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * System messages carry no author, so nothing should be resolved for them.
     */
    public function isSystem(): bool
    {
        return $this->type === MessageType::System;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MessageType::class,
            'edited_at' => 'datetime',
        ];
    }

    /**
     * @return MessageFactory
     */
    protected static function newFactory(): Factory
    {
        return MessageFactory::new();
    }
}
