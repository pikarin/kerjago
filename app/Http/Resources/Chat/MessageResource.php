<?php

namespace App\Http\Resources\Chat;

use App\Chat\Data\MessagePayload;
use App\Chat\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    /**
     * Delegates entirely to MessagePayload, which the broadcast event also uses.
     * That is what guarantees a message cannot render one way over the socket
     * and differently after a refresh.
     *
     * Note there is no author name here — only participant_id. Names come from
     * the conversation's participant map, resolved once per request.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Message $message */
        $message = $this->resource;

        return [
            ...MessagePayload::fromMessage($message)->toArray(),
            'reactions' => $message->relationLoaded('reactions')
                ? $message->reactions
                    ->groupBy('emoji')
                    ->map(fn ($group, $emoji) => [
                        'emoji' => $emoji,
                        'count' => $group->count(),
                        'participant_ids' => $group->pluck('participant_id')->all(),
                    ])
                    ->values()
                    ->all()
                : [],
        ];
    }
}
