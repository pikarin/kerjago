<?php

namespace App\Chat\Actions;

use App\Chat\Data\NewConversation;
use App\Chat\Models\Conversation;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class OpenConversation
{
    /**
     * Open a conversation, or return the existing one when `uniqueKey` is set.
     *
     * Idempotency is enforced by the database rather than by a read-then-write
     * check, so two simultaneous "apply and chat" requests cannot both succeed:
     * the loser catches the unique violation and returns the winner's row.
     */
    public function handle(NewConversation $new): Conversation
    {
        if ($new->uniqueKey !== null) {
            $existing = $this->findByUniqueKey($new->uniqueKey);

            if ($existing !== null) {
                return $existing;
            }
        }

        try {
            return DB::transaction(fn (): Conversation => $this->create($new));
        } catch (UniqueConstraintViolationException $exception) {
            if ($new->uniqueKey === null) {
                throw $exception;
            }

            // Lost the race. The conversation the winner created is the answer.
            return $this->findByUniqueKey($new->uniqueKey)
                ?? throw $exception;
        }
    }

    private function findByUniqueKey(string $uniqueKey): ?Conversation
    {
        return Conversation::query()
            ->with('participants')
            ->where('unique_key', $uniqueKey)
            ->first();
    }

    private function create(NewConversation $new): Conversation
    {
        $conversation = Conversation::query()->create([
            'kind' => $new->kind,
            'context_type' => $new->contextType,
            'context_id' => $new->contextId,
            'unique_key' => $new->uniqueKey,
            'meta' => $new->meta,
            'created_by_participant_id' => $new->createdByParticipantId,
        ]);

        $now = now();
        $withheld = array_flip($new->withheldParticipantIds);

        foreach (array_unique([...$new->participantIds, ...$new->withheldParticipantIds]) as $participantId) {
            $conversation->participants()->create([
                'participant_id' => $participantId,
                'joined_at' => $now,
                // A withheld participant is a member on paper only: `left_at`
                // is what every gate in the module reads, so one column keeps
                // the inbox, the authorizer and the presence channel agreeing.
                'left_at' => isset($withheld[$participantId]) ? $now : null,
            ]);
        }

        return $conversation->load('participants');
    }
}
