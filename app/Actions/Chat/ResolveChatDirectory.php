<?php

namespace App\Actions\Chat;

use App\Chat\Contracts\ContextResolver;
use App\Chat\Contracts\ParticipantResolver;
use App\Chat\Models\Conversation;
use App\Support\Chat\ChatDirectory;

class ResolveChatDirectory
{
    public function __construct(
        private ParticipantResolver $participants,
        private ContextResolver $contexts,
    ) {}

    /**
     * Collect every participant and context across a set of conversations and
     * resolve them in one pass.
     *
     * One resolver call for participants, plus one per distinct context type.
     * The count does not grow with the number of conversations or messages,
     * which is the property that has to survive extraction.
     *
     * @param  iterable<Conversation>  $conversations
     */
    public function handle(iterable $conversations): ChatDirectory
    {
        $participantIds = [];
        $contextIdsByType = [];

        foreach ($conversations as $conversation) {
            foreach ($conversation->participantIds() as $participantId) {
                $participantIds[$participantId] = true;
            }

            if ($conversation->context_type !== null && $conversation->context_id !== null) {
                $contextIdsByType[$conversation->context_type][$conversation->context_id] = true;
            }
        }

        $contexts = [];

        foreach ($contextIdsByType as $type => $ids) {
            foreach ($this->contexts->resolve($type, array_keys($ids)) as $id => $context) {
                $contexts["{$type}:{$id}"] = $context;
            }
        }

        return new ChatDirectory(
            participants: $this->participants->resolve(array_keys($participantIds)),
            contexts: $contexts,
        );
    }
}
