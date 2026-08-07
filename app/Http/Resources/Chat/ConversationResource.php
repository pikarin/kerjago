<?php

namespace App\Http\Resources\Chat;

use App\Chat\Models\Conversation;
use App\Chat\Models\Participant;
use App\Support\Chat\ChatDirectory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    public function __construct(
        Conversation $resource,
        private ChatDirectory $directory,
        private string $viewerId,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Conversation $conversation */
        $conversation = $this->resource;

        $context = $this->directory->context($conversation->context_type, $conversation->context_id);

        return [
            'id' => $conversation->id,
            'kind' => $conversation->kind,

            // Null when unbound, as cold outreach is.
            'context' => $context === null ? null : [
                'type' => $context->type,
                'label' => $context->label,
                'url' => $context->url,
                'unavailable' => $context->isPlaceholder,
            ],

            'participants' => $this->participants($conversation),

            // Asked of the policy rather than recomputed here, so the composer
            // is disabled on exactly the threads the write endpoint would
            // refuse — including the staff-thread exemption, which the client
            // must not have to know about a second time.
            'can_send_message' => Gate::allows('sendMessage', $conversation),

            'unread_count' => $conversation->unread_count ?? 0,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
        ];
    }

    /**
     * Everyone in the conversation, viewer included, so the client can label
     * any message by its participant_id without a second lookup.
     *
     * @return list<array<string, mixed>>
     */
    private function participants(Conversation $conversation): array
    {
        return array_values($conversation->participants
            ->map(function (Participant $participant): array {
                $identity = $this->directory->participant($participant->participant_id);

                return [
                    'id' => $identity->id,
                    'name' => $identity->name,
                    'avatar_url' => $identity->avatarUrl,
                    'unavailable' => $identity->isPlaceholder,
                    'is_viewer' => $identity->id === $this->viewerId,
                    'last_read_at' => $participant->last_read_at?->toIso8601String(),
                    'left_at' => $participant->left_at?->toIso8601String(),
                ];
            })
            ->all());
    }
}
